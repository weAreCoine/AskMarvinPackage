<?php

declare(strict_types=1);

namespace Marvin\Ask\Commands\Documents;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Marvin\Ask\Models\Document;

class DeleteOrphans extends Command implements Isolatable
{
    public bool $dryRun = false;
    protected string $maintenanceSecret;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ask:delete-orphans-documents {--M|months= : Check only last N months } {--dry-run : Do not delete anything, just show what would be deleted}';
    protected $aliases = ['documents:delete-orphans', 'documents:remove-orphans'];
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all Documents models that do not have a corresponding file on the disk.';
    protected Filesystem $disk;
    protected int $monthsToCheck;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->monthsToCheck = (int)$this->option('months');
        $this->dryRun = (bool)$this->option('dry-run');

        $this->disk = Storage::disk(Document::$disk);


        $this->sanitize();
        return self::SUCCESS;
    }

    protected function sanitize(): void
    {
        $report = [];
        Document::orderBy('id')
            ->when($this->monthsToCheck > 0,
                fn(Builder $query) => $query->where('created_at', '>=', now()->subMonths($this->monthsToCheck)))
            ->chunk(100,
                function (Collection $documents) use (&$report) {
                    $documents->each(function (Document $document) use (&$report) {
                        if (!$this->disk->exists($document->path)) {
                            $report[] = $document->path;
                            if (!$this->dryRun) {
                                $document->forceDeleteQuietly();
                            }
                        }
                    });
                });

        if (empty($report)) {
            $this->info('No orphan files found.');
        } else {
            $this->info('Orphan files found:');
            $this->table(['Path'], $report);
        }

        if ($this->dryRun) {
            $this->info('Dry run completed. If you want to delete the files, run the command without --dry-run option.');
        }
    }


}
