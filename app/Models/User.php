<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'type',
        'photo',
        'status',
        'password',
        'third_party',
        'device_token',
    ];


    protected $casts = [
        'email' => 'string',
        'device_token'=>'string',
        'name'=>'string',
        'photo'=>'string',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'third_party' => 'boolean',
    ];

    public static array $rules = [
        'email' => 'required|unique:users',
        'name' => 'required|string',
        'third_party' => 'required|boolean',
        'device_token' => 'string',
        'password' => [
            'required',
            'string',
            'confirmed',
            'min:10',             // must be at least 10 characters in length
            'regex:/[a-z]/',      // must contain at least one lowercase letter
            'regex:/[A-Z]/',      // must contain at least one uppercase letter
            'regex:/[0-9]/',      // must contain at least one digit
            'regex:/[@$!%*#?&]/', // must contain a special character
        ],
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */

    public $hidden = [ 'password','remember_token','code','updated_at','created_at','deleted_at'];
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */



    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }


    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function funies()
    {
        return $this->hasMany(Funny::class);
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'followed_id', 'follower_id')
            ->withTimestamps();
    }

    // Relationship: A user can be following many others
    public function followings()
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'followed_id')
            ->withTimestamps();
    }

    
     // Method: Check if the user is following another user
     public function isFollowing(User $user)
     {
         return $this->followings->contains($user);
     }

     // Method: Follow another user
     public function follow(User $user)
     {
         $this->followings()->attach($user->id);
     }

     // Method: Unfollow a user
     public function unfollow(User $user)
     {
         $this->followings()->detach($user->id);
     }

}
