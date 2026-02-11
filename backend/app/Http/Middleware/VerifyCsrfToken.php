<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    protected $addHttpCookie = true;
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/pay-via-ajax',
        '/success',
        '/cancel',
        '/fail',
        '/ipn',
        'admin/login', // avoid 419 when session cookie not sent (e.g. 127.0.0.1 vs localhost)
    ];
}
