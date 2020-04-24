<?php

namespace Isotopes\Profiler\Models;

use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Jenssegers\Mongodb\Eloquent\Builder;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * Class EntryModel
 * @package Isotopes\Profiler\Models
 * @property string uuid
 * @property string sequence
 * @property string batch_id
 * @property string type
 * @property string family_hash
 * @property array content
 * @property array tags
 * @property Carbon created_at
 */
class EntryModel extends Model
{
    /**
     * Collection associated with the model.
     *
     * @var string
     */
    protected $collection = 'profiler_entries';

    /**
     * The name of the "updated_at" column.
     *
     * @var string
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'content' => 'json',
    ];

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * Prevent Eloquent from overriding uuid with `lastInsertId`.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Scope the query for the given query options.
     *
     * @param Builder $query
     * @param string $type
     * @param EntryQueryOptions $options
     * @return Builder
     */
    public function scopeWithProfilerOptions(Builder $query, $type, EntryQueryOptions $options): Builder
    {
        $this->whereType($query, $type)
            ->whereBatchId($query, $options)
            ->whereTag($query, $options)
            ->whereFamilyHash($query, $options)
            ->whereBeforeSequence($query, $options)
            ->filter($query, $options);
        return $query;
    }

    /**
     * Scope the query for the given type.
     *
     * @param Builder $query
     * @param string $type
     * @return $this
     */
    protected function whereType(Builder $query, $type): self
    {
        $query->when($type, static function ($query, $type) {
            return $query->where('type', $type);
        });

        return $this;
    }

    /**
     * Scope the query for the given batch ID.
     *
     * @param Builder $query
     * @param EntryQueryOptions $options
     * @return $this
     */
    protected function whereBatchId(Builder $query, EntryQueryOptions $options): self
    {
        $query->when($options->batchId, static function ($query, $batchId) {
            return $query->where('batch_id', $batchId);
        });

        return $this;
    }

    /**
     * Scope the query for the given type.
     *
     * @param Builder $query
     * @param EntryQueryOptions $options
     * @return $this
     */
    protected function whereTag(Builder $query, EntryQueryOptions $options): self
    {
        $query->when($options->tag, static function ($query, $tag) {
            return $query->whereIn('uuid', static function ($query) use ($tag) {
                $query
                    ->select('entry_uuid')
                    ->from('profiler_entries_tags')
                    ->where('tag', $tag);
            });
        });

        return $this;
    }

    /**
     * Scope the query for the given type.
     *
     * @param Builder $query
     * @param EntryQueryOptions $options
     * @return $this
     */
    protected function whereFamilyHash(Builder $query, EntryQueryOptions $options): self
    {
        $query->when($options->familyHash, static function ($query, $hash) {
            return $query->where('family_hash', $hash);
        });

        return $this;
    }

    /**
     * Scope the query for the given pagination options.
     *
     * @param Builder $query
     * @param EntryQueryOptions $options
     * @return $this
     */
    protected function whereBeforeSequence(Builder $query, EntryQueryOptions $options): self
    {
        $query->when($options->beforeSequence, static function ($query, $beforeSequence) {
            return $query->where('sequence', '<', $beforeSequence);
        });

        return $this;
    }

    /**
     * Scope the query for the given display options.
     *
     * @param Builder $query
     * @param EntryQueryOptions $options
     * @return $this
     */
    protected function filter(Builder $query, EntryQueryOptions $options): self
    {
        if ($options->familyHash || $options->tag || $options->batchId) {
            return $this;
        }

        return $this;
    }

    /**
     * Get the current connection name for the model.
     *
     * @return Repository|Application|mixed|string|null
     */
    public function getConnectionName()
    {
        return config('profiler.storage.database.connection');
    }
}
