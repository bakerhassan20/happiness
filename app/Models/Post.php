<?php

namespace App\Models;

use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'joke',
        'reactions',
        'shares',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function funnies()
    {
        return $this->hasMany(Funny::class);
    }

     public function shares()
    {
        return $this->hasMany(Share::class);
    }

    public function checkFunny()
    {
        $user = auth()->user();
        $post = Post::find($this->id);

        $hasFunny = $user->funies()->where('post_id', $post->id)->exists();

        return $hasFunny;
    }


    public function checkfavorite()
    {
        $user = auth()->user();
        $post = Post::find($this->id);

        $hasFavorited = $user->favorites()->where('post_id', $post->id)->exists();

        return $hasFavorited;
    }

}
