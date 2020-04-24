<?php

namespace Isotopes\Profiler;

use Illuminate\Support\Arr;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * Class FormatModel
 * @package Isotopes\Profiler
 */
class FormatModel
{
    /**
     * @param Model $model
     * @return string
     */
    public static function given(Model $model): string
    {
        return get_class($model).':'.implode('_', Arr::wrap($model->getKey()));
    }
}
