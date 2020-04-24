<?php

namespace Isotopes\Profiler\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Isotopes\Profiler\Profiler;

class Authorize
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed|void
     */
    public function handle(Request $request, Closure $next)
    {
        return Profiler::check($request) ? $next($request) : abort(404);
    }
}
