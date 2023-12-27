<?php

namespace App\Http\Controllers\API;

use App\Models\Post;
use App\Models\Funny;
use App\Models\Share;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\PostRequest;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\PostCollection;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\AppBaseController;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PostsController extends AppBaseController
{
    public function __construct(){

        $this->middleware(['auth:api','verified']);

    }

    public function index()
    {
        $user = Auth::user();
        $posts = Post::inRandomOrder()->paginate(15);
       /*  $posts = Post::select('posts.*')
        ->leftJoin('funnies', 'posts.id', '=', 'funnies.post_id')
        ->groupBy('posts.id', 'posts.user_id', 'posts.joke', 'posts.reactions', 'posts.shares', 'posts.created_at', 'posts.updated_at')
        ->orderByRaw('posts.user_id = '.$user->id. ' DESC,MAX(posts.created_at) DESC, COUNT(funnies.id) DESC')
        ->paginate(10); */
        return $this->json_custom_response(new PostCollection($posts));

    }


    public function create(PostRequest $request){

        $data = $request->all();
        $data['user_id'] = auth()->user()->id;
        $data['reactions'] = 0;
        $data['shares'] = 0;
        $post = Post::create($data);

        return $this->sendResponse(["post" => new PostResource($post)], 'Post Created successfully');
    }

    public function update(Request $request){


        $validator = Validator::make($request->all(), [
            'joke'=>'required',
            'post_id'=>'required',
        ]);
        if ($validator->fails()) {

            throw new HttpResponseException(response()->json([
                'errors' => $validator->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));

        }
        $data = $request->all();
        $user = auth()->user();
        $post = Post::where('id',$data['post_id'])->where('user_id',$user->id)->first();

        if($post){
            $post->update($request->only([
                'joke',
            ]));

            return $this->sendResponse(["post" => new PostResource($post)], 'Post update successfully');
        }

        return $this->sendError('Post not found.');

    }

    public function delete(Request $request){

        $validator = Validator::make($request->all(), [
            'post_id'=>'required',
        ]);
        if ($validator->fails()) {

            throw new HttpResponseException(response()->json([
                'errors' => $validator->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));

        }
        $data = $request->all();
        $user = auth()->user();
        $post = Post::where('id',$data['post_id'])->where('user_id',$user->id)->first();

        if($post){
            $post->delete();
            return $this->sendSuccess('Post deleted successfully');
        }

        return $this->sendError('Post not found.');

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
            return $this->sendResponse(["reactions" => $post->reactions,'isfunny' => $post->checkFunny()], 'Post Unfunny Successfully');

        }

        $funny = new Funny();
        $funny->user_id = $user->id;
        $funny->post_id = $post->id;
        $funny->save();
        $post->increment('reactions');



        $postId = $post->id;
        $userName = $user->name;

        $this->sendFCMNotification($post->user->device_token, $postId, $userName);

        return $this->sendResponse(["reactions" => $post->reactions,'isfunny' => $post->checkFunny()], 'Post Funny Successfully');
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
                return $this->sendResponse([ 'isfavorite' => $post->checkfavorite()], 'Post removed from favorites successfully');
                //return $this->sendSuccess('Post removed from favorites successfully');
            }

            // Create a new favorite
            $favorite = new Favorite();
            $favorite->user_id = $user->id;
            $favorite->post_id = $post->id;
            $favorite->save();

            return $this->sendResponse([ 'isfavorite' => $post->checkfavorite()], 'Post added to favorites successfully');
            //return $this->sendSuccess('Post added to favorites successfully');

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


    public function getUsersReactedToPost($postId)
    {
        $post = Post::findOrFail($postId);
        $reactedUsers = $post->reactedByUsers()->select('name','photo')->get();
        return response()->json(['reacted_users' => $reactedUsers]);
    }



    private function sendFCMNotification($fcmToken, $postId, $userName)
    {
        $serverKey = 'your-firebase-server-key';

        $url = 'https://fcm.googleapis.com/fcm/send';

        $headers = [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $data = [
            'to' => $fcmToken,
            'notification' => [
                'title' => 'New Reaction!',
                'body' => $userName .'reacted to your post.',
            ],
            'data' => [
                'post_id' => $postId,
                'userName' => $userName,
            ],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Only for local testing, do not use in production
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            // Handle error
            error_log('FCM Notification Error: ' . curl_error($ch));
        }

        curl_close($ch);
    }


}
