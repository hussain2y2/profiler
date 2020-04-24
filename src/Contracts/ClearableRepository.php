<?php

namespace Isotopes\Profiler\Contracts;

/**
 * Interface ClearableRepository
 * @package Isotopes\Profiler\Contracts
 */
interface ClearableRepository
{
    /**
     * Clear all the entries.
     *
     * @return void
     */
    public function clear(): void;
}
