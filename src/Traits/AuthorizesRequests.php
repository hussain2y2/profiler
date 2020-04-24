<?php

namespace Isotopes\Profiler\Traits;

use Illuminate\Http\Request;

trait AuthorizesRequests
{
    /**
     * @var \Closure
     */
    public static $authUsing;

    /**
     * The callback that should be used to authenticate Profiler users.
     *
     * @param \Closure $callback
     * @return static
     */
    public static function auth($callback): AuthorizesRequests
    {
        static::$authUsing = $callback;

        return new static;
    }

    /**
     * Determine if the given request can access the Profiler dashboard.
     *
     * @param Request $request
     * @return mixed
     */
    public static function check(Request $request)
    {
        return (static::$authUsing ?: static function() {
            return app()->environment('local');
        }) ($request);
    }
}
