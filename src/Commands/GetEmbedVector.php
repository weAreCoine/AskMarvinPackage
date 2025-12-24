<?php

namespace Marvin\Ask\Commands;

use Illuminate\Console\Command;
use Marvin\Ask\Services\LlmService;

class GetEmbedVector extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ask:embed {message}';

    protected $aliases = ['embed'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the embed vector for a message';

    /**
     * Execute the console command.
     */
    public function handle(LlmService $llmService)
    {
        $message = $this->argument('message');
        $this->newLine();
        echo implode(',', $llmService->embed($message)[0]);
    }
}
