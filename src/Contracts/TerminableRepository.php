<?php

namespace Isotopes\Profiler\Contracts;

/**
 * Interface TerminableRepository
 * @package Isotopes\Profiler\Contracts
 */
interface TerminableRepository
{
    /**
     * Perform any clean-up tasks needed after storing Profiler entries.
     *
     * @return void
     */
    public function terminate(): void;
}
