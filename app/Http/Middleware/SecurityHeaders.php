<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline browser hardening for every web response.
 *
 * The CSP is Report-Only on purpose: the approved UI uses a small inline
 * theme script and a few inline `style` attributes, so enforcing a policy
 * without a nonce/hash build step would break the page. Report-Only lets an
 * operator collect violations first; SECURITY.md documents that trade-off.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Do not advertise the PHP build; the framework already omits its own.
        header_remove('X-Powered-By');

        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
            'Content-Security-Policy-Report-Only' => implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "object-src 'none'",
                "frame-ancestors 'none'",
                "form-action 'self'",
                "img-src 'self' data:",
                "font-src 'self' data: https://fonts.gstatic.com",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "script-src 'self' 'unsafe-inline'",
                "connect-src 'self'",
            ]),
        ];

        // HSTS only makes sense on a TLS request. Behind a reverse proxy the
        // operator must configure TrustProxies for isSecure() to be accurate.
        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
