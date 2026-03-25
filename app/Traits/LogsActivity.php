<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait LogsActivity
{
    /**
     * Log an activity for this model
     */
    public function logActivity(string $action, ?string $description = null, ?array $properties = null)
    {
        return ActivityLog::log($action, $this, $description, $properties);
    }

    /**
     * Get all activity logs for this model
     */
    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'model');
    }
}
