<?php

namespace Isotopes\Profiler;

use Jenssegers\Mongodb\Eloquent\Model;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * Class ExtractProperties
 * @package Isotopes\Profiler
 */
class ExtractProperties
{
    /**
     * Extract the properties for the given object in array form.
     *
     * The given array is ready for storage.
     *
     * @param mixed $target
     * @return array
     * @throws ReflectionException
     */
    public static function from($target): array
    {
        return collect((new ReflectionClass($target))->getProperties())
            ->mapWithKeys(static function(ReflectionProperty $property) use ($target) {
                $property->setAccessible(true);

                if (($value = $property->getValue($target)) instanceof Model) {
                    return [$property->getName() => FormatModel::given($value)];
                } elseif (is_object($value)) {
                    return [
                        $property->getName() => [
                            'class'      => get_class($value),
                            'properties' => json_decode(json_encode($value), true),
                        ],
                    ];
                } else {
                    return [$property->getName() => json_decode(json_encode($value), true)];
                }
            })->toArray();
    }
}
