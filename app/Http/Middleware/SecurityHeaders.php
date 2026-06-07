<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');

        // CSP permissiva ma sicura per AdminLTE + Livewire + Alpine.js + FullCalendar.
        // 'unsafe-inline' necessario per Livewire (wire:click inline handlers) e Alpine.js (x-data inline).
        // blob: in img-src necessario per le anteprime foto caricate via Livewire.
        // cdn.jsdelivr.net in script-src per jsQR caricato dal layout tablet.
        $response->headers->set('Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; " .
            "style-src 'self' 'unsafe-inline'; " .
            "img-src 'self' data: blob:; " .
            "connect-src 'self'; " .
            "font-src 'self' data:; " .
            "frame-ancestors 'none';"
        );

        return $response;
    }
}
