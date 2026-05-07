<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    /**
     * Redirect every non-secure request to HTTPS when enabled.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.force_https', true)
            && ! app()->runningUnitTests()
            && ! $request->secure()
            && $request->method() === 'GET') {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
