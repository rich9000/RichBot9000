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
    use  HasApiTokens, Notifiable, MustVerifyEmail;


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

    public function assistants()
    {
        return $this->hasMany(Assistant::class);
    }

    public function toolGroups()
    {
        return $this->belongsToMany(ToolGroup::class, 'tool_group_user');
    }

    protected $with = ['roles', 'toolGroups'];

    public function hasRole($role)
    {
        // Convert single role to array for consistent handling
        $roles = is_array($role) ? $role : [$role];
        
        // Convert all roles to lowercase for case-insensitive comparison
        $roles = array_map('strtolower', $roles);
        
        // Check if user has any of the specified roles
        return $this->roles->whereIn('name', $roles)->isNotEmpty();
    }

    public function eventLogs()
    {
        return $this->morphMany(EventLog::class, 'loggable');
    }

    /**
     * Override the default email verification notification to use our custom API-based system
     */
    public function sendEmailVerificationNotification()
    {
        // Use our custom email verification controller instead of Laravel's built-in one
        $controller = new \App\Http\Controllers\EmailVerificationController();
        
        // Create a request with the user
        $request = new \Illuminate\Http\Request();
        $request->setUserResolver(function () {
            return $this;
        });
        
        // Send the verification token
        return $controller->requestEmailVerificationToken($request);
    }

}
