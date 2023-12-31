<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'photo' => $this->photo,
            'bio' => $this->bio,
            'created_at' =>  $this->created_at?->format('Y-m-d') ?? null,
            'Posts' =>  $this->posts->count(),
            'followings' =>  $this->followings->count(),
            'followers' =>  $this->followers->count(),
            ];
    }
}
