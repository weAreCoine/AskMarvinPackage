<?php

namespace Marvin\Ask\Commands\Emails;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Carbon;
use Marvin\Ask\Actions\GenerateAnswerAction;
use Marvin\Ask\Entities\Email\EmailMessage;
use Marvin\Ask\Handlers\ExceptionsHandler;
use Marvin\Ask\Models\CommandRun;
use Marvin\Ask\Models\Email;
use Marvin\Ask\Services\EmailService;
use Str;

class GenerateEmailReplies extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = <<<'SIGNATURE'
            emails:reply
            {--O|only= : Process only the email from the provided email address}
            {--U|unread : Filter only unread emails}
            {--L|limit=10 : Limit the number of emails to process. Minimum 1, maximum 100}
            {--F|force : Force the email to be processed even if it has already been processed}
            {--D|datetime= : Parse only emails received after the provided datetime. Format: Y-m-d H:i:s}
            {--S|scheduled : If true, the run will be stored in the database table. If [datetime] is not provided, will be used the latest successful run start time instead}
    SIGNATURE;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reply to emails';

    protected array $allowedEmailDomains = ['askmarvin.it', 'coine.it'];

    protected bool $force = false;
    protected int $limit = 1;
    protected bool $unreadOnly = true;
    protected ?string $only = null;
    protected ?string $datetime = null;
    protected bool $scheduled = false;
    protected ?Carbon $since = null;
    protected ?GenerateAnswerAction $action = null;
    protected int $totalProcessedEmails = 0;
    protected EmailService $client;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->only = $this->option('only');
        $this->unreadOnly = $this->option('unread');
        $this->limit = min(100, max(1, (int)$this->option('limit')));
        $this->force = $this->option('force');
        $this->datetime = $this->option('datetime');
        $this->scheduled = $this->option('scheduled');

        if (!empty($datetime)) {
            $this->since = Carbon::parse($this->datetime, 'Europe/Rome');
        } elseif ($this->scheduled) {
            $this->since = CommandRun::whereSuccess(true)
                ->orderBy('started_at', 'desc')
                ->first()?->started_at ?? Carbon::now()->subHours(12);
        }

        $commandRun = CommandRun::create([
            'command' => 'emails:reply',
            'options' => $this->options(),
            'started_at' => Carbon::now(),
            'finished_at' => null,
            'success' => false,
        ]);

        if (empty($this->only)) {
            $emailAddresses = ['chatbot@askmarvin.it'];
        } else {
            $emailAddresses = [$this->only];
        }

        try {
            $this->action = GenerateAnswerAction::make()->init(
                answerGenerationPromptName: 'email_answer_generation',
                traceName: 'marvin_email_',
            );
        } catch (Exception $e) {
            ExceptionsHandler::handle($e);
            $this->error('Error initializing GenerateAnswerAction');

            return self::FAILURE;
        }

        foreach ($emailAddresses as $emailAddress) {
            $this->generateResponses($emailAddress);
        }

        $commandRun->update([
            'finished_at' => Carbon::now(),
            'success' => true,
            'output' => sprintf('Processed %d emails.', $this->totalProcessedEmails),
        ]);

        return self::SUCCESS;
    }

    protected function generateResponses(string $emailAddress): void
    {
        $this->client = new EmailService($emailAddress);

        $emails = $this->client->getInboxMessages(
            $this->limit,
            unreadOnly: $this->unreadOnly,
            since: $this->since,
        );
        foreach ($emails as $email) {
            $response = $this->respondToEmail($email);

            if (is_string($response)) {
                $this->warn(sprintf('Email (#%s) skipped. Reason: %s', $email->id, $response));
            }

            if ($response) {
                $this->info(sprintf('Email (#%s) processed.', $email->id));
            } else {
                $this->error(sprintf('Email (#%s) not processed.', $email->id));
            }
        }
    }

    /**
     * Responds to an email by generating a reply and updating the relevant database records.
     *
     * @param EmailMessage $email The email message to process.
     * @reurn bool|string Returns true if successfully processed, false if an error occurred,
     *                     or a string reason if the email does not require a response.
     */
    protected function respondToEmail(EmailMessage $email): bool|string
    {
        $needsAnswerGeneration = $this->needsAnswerGeneration($email);
        if ($needsAnswerGeneration !== true) {
            $this->warn(sprintf('Email (#%s) skipped. Reason: %s', $email->id, $needsAnswerGeneration));
            return $needsAnswerGeneration;
        }

        try {
            $replyText = $this->action->run($email->text);
            if ($replyText === false) {
                return false;
            }
            $draftId = $this->client->reply($email, $replyText);
            $this->totalProcessedEmails++;
            Email::updateOrCreate(
                [
                    'email_id' => $email->id,
                ],
                [
                    'from' => $email->from,
                    'email_content' => $email->text,
                    'reply_content' => $replyText,
                    'reply_id' => $draftId,
                    'is_sent' => false,
                    'responded_at' => Carbon::now(),
                ]);
            return true;
        } catch (Exception $e) {
            ExceptionsHandler::handle($e);
            return false;
        }
    }

    protected function needsAnswerGeneration(EmailMessage $email): true|string
    {

        if (!in_array($this->getEmailSenderDomain($email->from), $this->allowedEmailDomains)) {
            return 'sender domain not allowed';
        }

        if (Email::whereEmailId($email->id)->exists() && !$this->force) {
            return 'already processed';
        }

        return true;
    }

    protected function getEmailSenderDomain(string $emailString): string
    {
        if (preg_match('/<([^>]+)>/', $emailString, $matches)) {
            $email = $matches[1];
        } else {
            $email = $emailString;
        }

        return Str::after($email, '@') ?: '';
    }
}
