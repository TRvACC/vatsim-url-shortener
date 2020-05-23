<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Kyslik\ColumnSortable\Sortable;

/**
 * App\Models\Url
 *
 * @property int $id
 * @property int|null $organization_id
 * @property int|null $user_id
 * @property int $domain_id
 * @property bool $prefix
 * @property string $url
 * @property string $redirect_url
 * @property int $analytics_disabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Domain $domain
 * @property-read string $full_url
 * @property-read \App\Models\Organization|null $organization
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Url newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Url newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Url onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Url public()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Url query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Url sortable($defaultParameters = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Url whereAnalyticsDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Url whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Url whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Url whereDomainId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Url whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Url whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Url wherePrefix($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Url whereRedirectUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Url whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Url whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Url whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Url withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Url withoutTrashed()
 * @mixin \Eloquent
 */
class Url extends Model
{
    use SoftDeletes, Sortable;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'prefix' => 'boolean',
    ];

    /**
     * The attributes that are trackable.
     *
     * @var array
     */
    protected $tracked = ['user_id', 'organization_id', 'domain_id', 'url', 'redirect_url'];

    /**
     * Sortable attributes.
     *
     * @var array
     */
    public $sortable = [
        'id',
        'url',
        'redirect_url',
        'created_at',
        'updated_at',
    ];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = ['domain'];

    /**
     * The domain the URL relates to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * The URL's organization.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * The user the URL is owned by.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full URL.
     *
     * @return string
     */
    public function getFullUrlAttribute()
    {
        return $this->domain->url
            .($this->prefix ? $this->organization->prefix.'/' : '')
            .$this->url;
    }

    /**
     * Scope a query to only publicly viewable URLs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->whereNull('user_id')->whereNull('organization_id');
    }

    /**
     * Override default URL sorting in order to sort URLs by their
     * full host and path, instead of just their path.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param $direction
     * @return mixed
     */
    public function urlSortable($query, $direction)
    {
        $column = DB::connection()->getDriverName() == 'sqlite'
            ? DB::raw('(domains.url || COALESCE(organizations.prefix, "") || urls.url)')
            : DB::raw('CONCAT(domains.url, COALESCE(organizations.prefix, ""), urls.url)'); // TODO: test

        return $query->select(["{$this->getTable()}.*"])
            ->join('domains', 'urls.domain_id', 'domains.id')
            ->leftJoin('organizations', function ($join) {
                /* @var JoinClause $join */
                $join->on('urls.organization_id', 'organizations.id')
                    ->where('urls.prefix', true)
                    ->whereNotNull('organizations.prefix');
            })
            ->orderBy($column, $direction);
    }

    /**
     * Override default redirect URL sorting in order to sort URLs
     * without their protocol.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param $direction
     * @return mixed
     */
    public function redirectUrlSortable($query, $direction)
    {
        return $query->orderBy(DB::raw("REPLACE(REPLACE(redirect_url, 'https://', ''), 'http://', '')"), $direction);
    }
}
