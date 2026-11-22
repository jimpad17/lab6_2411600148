<?php

namespace App\Listeners;

use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Login;

class LogLogin
{
    public function handle(Login $event): void
    {
        ActivityLogger::log('logged in', 'Auth', 'Signed in successfully.', $event->user);
    }
}
