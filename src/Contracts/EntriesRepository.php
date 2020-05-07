<?php

namespace Isotopes\Profiler\Contracts;

use Illuminate\Support\Collection;
use Isotopes\Profiler\Entry\EntryResult;
use Isotopes\Profiler\Entry\EntryUpdate;
use Isotopes\Profiler\Entry\IncomingEntry;
use Isotopes\Profiler\Models\EntryQueryOptions;

/**
 * Interface EntriesRepository
 * @package Isotopes\Profiler\Contracts
 */
interface EntriesRepository
{
    /**
     * Return an entry with the given ID.
     *
     * @param $id
     * @return EntryResult
     */
    public function find($id): EntryResult;

    /**
     * Return all the entries of a given type.
     *
     * @param string|null $type
     * @param EntryQueryOptions $options
     * @return Collection|EntryResult[]
     */
    public function get($type, EntryQueryOptions $options);

    /**
     * Store the given entries.
     *
     * @param Collection|IncomingEntry[] $entries
     * @return void
     */
    public function store(Collection $entries);

    /**
     * Store the given entry updates.
     *
     * @param Collection|EntryUpdate[] $updates
     * @return void
     */
    public function update(Collection $updates);

    /**
     * Load the monitored tags from storage.
     *
     * @return void
     */
    public function loadMonitoredTags();

    /**
     * Determine if any of the given tags are currently being monitored.
     *
     * @param array $tags
     * @return bool
     */
    public function isMonitoring(array $tags);

    /**
     * Get the list of tags currently being monitored.
     *
     * @return array
     */
    public function monitoring();

    /**
     * Begin monitoring the given list of tags.
     *
     * @param  array  $tags
     * @return void
     */
    public function monitor(array $tags);

    /**
     * Stop monitoring the given list of tags.
     *
     * @param  array  $tags
     * @return void
     */
    public function stopMonitoring(array $tags);
}
