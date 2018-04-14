<?php

namespace App\Models;

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
 * @property string|null $remember_token
 * @property array $vatsim_sso_data
 * @property array $vatsim_status_data
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\EmailVerification $emailVerification
 * @property-read string $full_name
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereEmailVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\User whereRememberToken($value)
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
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
