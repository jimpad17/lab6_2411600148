<?php

namespace App\Listeners;

use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Logout;

class LogLogout
{
    public function handle(Logout $event): void
    {
        ActivityLogger::log('logged out', 'Auth', 'Signed out.', $event->user);
    }
}
