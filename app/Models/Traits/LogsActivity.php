<?php

namespace App\Models\Traits;

use App\Services\ActivityLogService;
use Illuminate\Support\Facades\App;

trait LogsActivity
{
    /**
     * Boot the trait
     */
    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->logCreated();
        });

        static::updated(function ($model) {
            $model->logUpdated();
        });

        static::deleted(function ($model) {
            $model->logDeleted();
        });
    }

    /**
     * Log model creation
     */
    protected function logCreated(): void
    {
        $this->getActivityLogger()->log(
            $this->getActivityAction('created'),
            $this->getActivityModel(),
            $this->getKey(),
            $this->getActivityData()
        );
    }

    /**
     * Log model update
     */
    protected function logUpdated(): void
    {
        if ($this->wasChanged()) {
            $this->getActivityLogger()->log(
                $this->getActivityAction('updated'),
                $this->getActivityModel(),
                $this->getKey(),
                [
                    'changes' => $this->getChanges(),
                    'original' => $this->getOriginal(),
                ]
            );
        }
    }

    /**
     * Log model deletion
     */
    protected function logDeleted(): void
    {
        $this->getActivityLogger()->log(
            $this->getActivityAction('deleted'),
            $this->getActivityModel(),
            $this->getKey(),
            []
        );
    }

    /**
     * Get activity logger instance
     */
    protected function getActivityLogger(): ActivityLogService
    {
        return App::make(ActivityLogService::class);
    }

    /**
     * Get activity action name
     */
    protected function getActivityAction(string $action): string
    {
        $modelName = strtolower(class_basename($this));
        return "{$modelName}.{$action}";
    }

    /**
     * Get activity model name
     */
    protected function getActivityModel(): string
    {
        return class_basename($this);
    }

    /**
     * Get activity data to log
     */
    protected function getActivityData(): array
    {
        // Override this method in models to customize logged data
        return $this->toArray();
    }
}

