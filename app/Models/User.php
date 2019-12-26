<?php

namespace App\Models;

use App\Services\VatsimService;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $email
 * @property bool $email_verified
 * @property string|null $totp_secret
 * @property string|null $remember_token
 * @property array|null $vatsim_sso_data
 * @property array|null $vatsim_status_data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Revision[] $dataChanges
 * @property-read int|null $data_changes_count
 * @property-read \App\Models\EmailVerification $emailVerification
 * @property-read string $display_info
 * @property-read string $full_name
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Organization[] $organizations
 * @property-read int|null $organizations_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Url[] $urls
 * @property-read int|null $urls_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereEmailVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereTotpSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereVatsimSsoData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereVatsimStatusData($value)
 * @mixin \Eloquent
 */
class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract
{
    use Authenticatable, Authorizable, Notifiable;

    /**
     * The attributes that are trackable.
     *
     * @var array
     */
    protected $tracked = [
        'first_name',
        'last_name',
        'email',
        'email_verified',
        'vatsim_sso_data',
        'vatsim_status_data',
    ];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token',
        'vatsim_sso_data',
        'vatsim_status_data',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified' => 'boolean',
        'vatsim_sso_data' => 'array',
        'vatsim_status_data' => 'array',
    ];

    /**
     * An email verification token model, if present.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function emailVerification()
    {
        return $this->hasOne(EmailVerification::class);
    }

    /**
     * The user's organizations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function organizations()
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot('role_id')
            ->withTimestamps()
            ->using(OrganizationUser::class)
            ->whereNull('organization_user.deleted_at');
    }

    /**
     * The user's short URLs.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function urls()
    {
        return $this->hasMany(Url::class);
    }

    /**
     * Determine if the user is an administrator.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return in_array($this->id, config('auth.admins'));
    }

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the user's display information.
     *
     * @return string
     */
    public function getDisplayInfoAttribute()
    {
        return "{$this->first_name} {$this->last_name} ({$this->id})";
    }

    /**
     * Create a new user from their Cert data.
     *
     * @param int $id
     * @return mixed
     * @throws \App\Exceptions\Cert\InvalidResponseException
     */
    public static function createFromCert(int $id)
    {
        $attributes = app(VatsimService::class)->getUser($id);

        $user = new self();
        $user->id = $attributes['id'];
        $user->first_name = $attributes['name_first'];
        $user->last_name = $attributes['name_last'];
        $user->vatsim_status_data = $attributes;
        $user->save();

        return $user;
    }
}
