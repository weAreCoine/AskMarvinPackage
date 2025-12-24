<?php

namespace Marvin\Ask\Commands;

use Exception;
use Illuminate\Console\Command;
use Marvin\Ask\Actions\GenerateAnswerAction;
use Marvin\Ask\Actions\ParallelGenerateAnswerAction;
use Marvin\Ask\Handlers\ExceptionsHandler;

class AskQuestion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ask:question {question}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ask a question to Marvin from the command line.';

    /**
     * Execute the console command.
     */
    public function handle(GenerateAnswerAction $generateAnswerAction)
    {
        $question = $this->argument('question');
        $this->newLine();

        try {
            $answer = $generateAnswerAction->init()->run($question);
            $this->info($answer);
            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error($e->getMessage());
            ExceptionsHandler::handle($e);
            return self::FAILURE;
        }
    }
}
