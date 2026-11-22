<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Single entry point used by controllers, middleware and auth listeners.
     */
    public static function log(string $action, string $module, string $description, ?User $user = null): void
    {
        ActivityLog::create([
            'user_id' => ($user ?? Auth::user())?->id,
            'action' => $action,
            'module' => $module,
            'description' => strip_tags($description),
            'ip_address' => request()->ip(),
        ]);
    }
}
