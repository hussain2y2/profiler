<?php

namespace Isotopes\Profiler\Models;

use Illuminate\Http\Request;

/**
 * Class EntryQueryOptions
 * @package Isotopes\Profiler\Models
 */
class EntryQueryOptions
{
    /**
     * The batch ID that entries should belong to.
     *
     * @var string
     */
    public $batchId;

    /**
     * The tag that must belong to retrieved entries.
     *
     * @var string
     */
    public $tag;

    /**
     * The family hash that must belong to retrieved entries.
     *
     * @var string
     */
    public $familyHash;

    /**
     * The ID that all retrieved entries should be less than.
     *
     * @var mixed
     */
    public $beforeSequence;

    /**
     * The list of UUIDs of entries tor retrieve.
     *
     * @var mixed
     */
    public $uuids;

    /**
     * The number of entries to retrieve.
     *
     * @var int
     */
    public $limit = 50;

    /**
     * Create new entry query options from the incoming request.
     *
     * @param Request $request
     * @return EntryQueryOptions
     */
    public static function fromRequest(Request $request): EntryQueryOptions
    {
        return (new static)
            ->batchId($request->batch_id)
            ->uuids($request->uuids)
            ->beforeSequence($request->before)
            ->tag($request->tag)
            ->familyHash($request->family_hash)
            ->limit($request->take ?? 50);
    }

    /**
     * Create new entry query options for the given batch ID.
     *
     * @param  string|null  $batchId
     * @return static
     */
    public static function forBatchId(?string $batchId): EntryQueryOptions
    {
        return (new static)->batchId($batchId);
    }

    /**
     * Set the batch ID for the query.
     *
     * @param  string|null  $batchId
     * @return $this
     */
    public function batchId(?string $batchId): self
    {
        $this->batchId = $batchId;

        return $this;
    }

    /**
     * Set the list of UUIDs of entries tor retrieve.
     *
     * @param  array|null  $uuids
     * @return $this
     */
    public function uuids(?array $uuids): self
    {
        $this->uuids = $uuids;

        return $this;
    }

    /**
     * Set the ID that all retrieved entries should be less than.
     *
     * @param  mixed  $id
     * @return $this
     */
    public function beforeSequence($id): self
    {
        $this->beforeSequence = $id;

        return $this;
    }

    /**
     * Set the tag that must belong to retrieved entries.
     *
     * @param  string|null  $tag
     * @return $this
     */
    public function tag(?string $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Set the family hash that must belong to retrieved entries.
     *
     * @param  string|null  $familyHash
     * @return $this
     */
    public function familyHash(?string $familyHash): self
    {
        $this->familyHash = $familyHash;

        return $this;
    }

    /**
     * Set the number of entries that should be retrieved.
     *
     * @param  int  $limit
     * @return $this
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }
}
