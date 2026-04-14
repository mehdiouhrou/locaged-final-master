<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AUDIT FIX #6: Security Headers Middleware
 * 
 * Adds security headers to all responses to protect against:
 * - XSS attacks (Content-Security-Policy, X-XSS-Protection)
 * - Clickjacking (X-Frame-Options SAMEORIGIN + CSP frame-ancestors)
 * - MIME sniffing (X-Content-Type-Options)
 * - Information leakage (Referrer-Policy)
 * - Protocol downgrade attacks (Strict-Transport-Security)
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent third-party clickjacking while allowing same-origin iframes (document/PDF preview).
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // XSS Protection (legacy browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy - only send origin for cross-origin requests
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (formerly Feature-Policy)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        // Ne pas envoyer Cross-Origin-Resource-Policy : avec COOP, le navigateur peut refuser
        // le worker PDF.js (.mjs) pour l’aperçu à l’upload (« Cross-origin script load / CORS »).

        // Content Security Policy - adapt to HTTP or HTTPS
        // Dynamically set protocol based on request
        $protocol = $request->secure() ? 'https:' : 'http: https:';
        
        $csp = implode('; ', [
            "default-src 'self' {$protocol}",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$protocol}",
            "style-src 'self' 'unsafe-inline' {$protocol}",
            "font-src 'self' {$protocol} data:",
            "img-src 'self' data: blob: {$protocol}",
            "connect-src 'self' wss: ws: {$protocol}",
            "worker-src 'self' blob:",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        // HSTS - only on HTTPS connections (1 year, include subdomains)
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }
}
