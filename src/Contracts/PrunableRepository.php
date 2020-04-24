<?php

namespace Isotopes\Profiler\Contracts;

use DateTimeInterface;

interface PrunableRepository
{
    /**
     * Prune all the entries older than the given date.
     *
     * @param DateTimeInterface $before
     * @return void
     */
    public function prune(DateTimeInterface $before);
}
