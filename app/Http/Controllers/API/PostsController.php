<?php

namespace App\Http\Controllers\API;

use App\Models\Post;
use App\Models\Funny;
use App\Models\Share;
use App\Models\Favorite;
use Illuminate\Http\Request;
use App\Http\Requests\PostRequest;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Http\Resources\PostCollection;
use App\Http\Controllers\API\AppBaseController;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PostsController extends AppBaseController
{
    public function __construct(){

        $this->middleware(['auth:api','verified']);

    }

    public function index()
    {
        $posts = Post::inRandomOrder()->paginate(10);
      //  return response()->json($posts);
        return $this->sendResponse(new PostCollection($posts), 'Get posts successfully');

    }


    public function create(PostRequest $request){

        $data = $request->all();
        $data['user_id'] = auth()->user()->id;
        $data['reactions'] = 0;
        $data['shares'] = 0;
        $post = Post::create($data);

        return $this->sendResponse(["post" => new PostResource($post)], 'Post Created successfully');
    }

    public function Funny_Post(Request $request,$postId){

        $user = auth()->user();
        $post = Post::find($postId);

        if (!$post) {
            return $this->sendError('Post not found.');
        }

        $existingFunny = Funny::where('user_id', $user->id)->where('post_id', $post->id)->first();

        if ($existingFunny) {

            $existingFunny->delete();
            $post->decrement('reactions');
            return $this->sendResponse(["reactions" => $post->reactions], 'Post Unfunny Successfully');

        }

        $funny = new Funny();
        $funny->user_id = $user->id;
        $funny->post_id = $post->id;
        $funny->save();
        $post->increment('reactions');
        return $this->sendResponse(["reactions" => $post->reactions], 'Post Funny Successfully');
    }



    public function add_Remove_Favorites(Request $request, $postId)
    {
        try {
            // Assuming you have authentication set up and the user is logged in
            $user = auth()->user();
            $post = Post::findOrFail($postId);

            if (!$post) {
                return $this->sendError('Post not found.');
            }

            $existingFavorite = Favorite::where('user_id', $user->id)->where('post_id', $post->id)->first();

            if ($existingFavorite) {

                $existingFavorite->delete();
                return $this->sendSuccess('Post removed from favorites successfully');
            }

            // Create a new favorite
            $favorite = new Favorite();
            $favorite->user_id = $user->id;
            $favorite->post_id = $post->id;
            $favorite->save();

            return $this->sendSuccess('Post added to favorites successfully');

        } catch (ModelNotFoundException $e) {
            return $this->sendError('Post not found.');

        }
    }


    public function sharePost(Request $request, $postId)
    {
        try {
            // Assuming you have authentication set up and the user is logged in
            $user = auth()->user();
            $post = Post::findOrFail($postId);

            // Create a new share
            $share = new Share();
            $share->user_id = $user->id;
            $share->post_id = $post->id;
            $share->save();
            $post->increment('shares');

            return $this->sendResponse(["shares" => $post->shares], 'Post shared successfully');

        } catch (ModelNotFoundException $e) {
        return $this->sendError('Post not found.');
        }
    }


   

}
