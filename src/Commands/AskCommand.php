<?php

namespace Marvin\Ask\Commands;

use Illuminate\Console\Command;

class AskCommand extends Command
{
    public $signature = 'ask';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
