<?php

/**
 * Router for `php artisan serve`.
 *
 * PHP's built-in server does not hand URLs with repeated leading slashes
 * (e.g. //////.......//////) to index.php when no router script is present.
 * Laravel's ServeCommand detects this file and uses it for every request,
 * so the application's fallback route can return the user to their page.
 */
// Do not use parse_url(): a URI like //....... is read as a scheme-relative
// URL (host = "......."), which would skip the front controller. Read the raw
// request target instead so every slash/dot combination reaches Laravel.
$uri = urldecode(explode('?', $_SERVER['REQUEST_URI'], 2)[0]);

// Serve real files (CSS, images, built assets) directly.
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
