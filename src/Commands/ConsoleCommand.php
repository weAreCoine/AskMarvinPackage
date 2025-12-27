<?php
declare(strict_types=1);

namespace Marvin\Ask\Commands;

use Illuminate\Console\Command;
use Marvin\Ask\Actions\GenerateAnswerAction;

class ConsoleCommand extends Command
{
    protected $signature = 'ask:console';

    public function handle(): void
    {
        $answer = GenerateAnswerAction::make()
            ->init()
            ->run('Ciao, come ti chiami?');
        $this->info($answer);
    }
}
