<?php

namespace App\Console\Commands\NawalaChecker;

use App\Models\NawalaChecker\Target;
use App\Services\NawalaChecker\CheckRunnerService;
use Illuminate\Console\Command;

class RunChecksCommand extends Command
{
    protected $signature = 'nawala:run-checks {--target-id=}';
    protected $description = 'Run checks for all enabled targets or specific target';

    public function __construct(
        protected CheckRunnerService $checkRunner
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $targetId = $this->option('target-id');

        if ($targetId) {
            $target = Target::find($targetId);
            if (!$target) {
                $this->error("Target with ID {$targetId} not found.");
                return 1;
            }

            $this->info("Running check for target: {$target->domain_or_url}");
            $result = $this->checkRunner->checkTarget($target);
            $this->info("Result: {$result['status']} (Confidence: {$result['confidence']}%)");

            return 0;
        }

        // Run checks for all enabled targets that need checking
        $targets = Target::enabled()
            ->where(function ($query) {
                $query->whereNull('last_checked_at')
                    ->orWhereRaw('last_checked_at < NOW() - INTERVAL check_interval SECOND');
            })
            ->get();

        $this->info("Found {$targets->count()} targets to check.");

        $bar = $this->output->createProgressBar($targets->count());
        $bar->start();

        foreach ($targets as $target) {
            try {
                $this->checkRunner->checkTarget($target);
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("\nError checking {$target->domain_or_url}: {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Checks completed.');

        return 0;
    }
}

