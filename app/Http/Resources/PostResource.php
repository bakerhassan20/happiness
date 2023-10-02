<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
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
            'joke' => $this->joke,
            'reactions' => $this->reactions,
            'shares' => $this->shares,
            'created_at' =>  $this->created_at?->format('Y-m-d h:i') ?? null,
            'user' => new UserResource($this->user),
            'isfunny' => $this->checkFunny(),
            'isfavorite' => $this->checkfavorite(),
            ];
    }
}
