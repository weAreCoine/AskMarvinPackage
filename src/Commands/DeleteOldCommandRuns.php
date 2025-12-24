<?php

namespace Marvin\Ask\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Marvin\Ask\Models\CommandRun;

class DeleteOldCommandRuns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ask:forget-commands-runs {--days=15 : Number of days to keep command runs}';

    protected $aliases = ['command-run:delete'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Forget command runs older than the specified number of days. Default 15.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $this->info("Deleting command runs older than $days days...");
        $query = CommandRun::where('created_at', '<', now()->subDays($days));
        $count = $query->count();
        $query->delete();
        $this->info(sprintf('%s successfully deleted.', Str::plural('row', $count, true)));

        return self::SUCCESS;
    }
}
