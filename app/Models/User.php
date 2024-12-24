<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\MustVerifyEmail;


class User extends Authenticatable
{
    use  HasApiTokens;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable = [
        'name', 'email', 'password', 'phone_number', 'phone_verification_token', 'email_verification_token',  // other fields...
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'user_contacts')
            ->withPivot('context', 'allowed_to_contact','name')
            ->withTimestamps();
    }
    public function media()
    {
        return $this->hasMany(Media::class);
    }
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
    public function hasRole($role)
    {
        return $this->roles->contains('name', $role);
    }

    public function eventLogs()
    {
        return $this->morphMany(EventLog::class, 'loggable');
    }


}
