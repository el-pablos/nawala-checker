<?php

namespace App\Console\Commands\NawalaChecker;

use App\Services\NawalaChecker\ShortlinkRotationService;
use Illuminate\Console\Command;

class AutoRotateCommand extends Command
{
    protected $signature = 'nawala:auto-rotate';
    protected $description = 'Auto-rotate shortlinks based on target status';

    public function __construct(
        protected ShortlinkRotationService $rotationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Running auto-rotation...');

        $rotatedCount = $this->rotationService->autoRotateAll();

        $this->info("Auto-rotation completed. Rotated {$rotatedCount} shortlinks.");

        return 0;
    }
}

