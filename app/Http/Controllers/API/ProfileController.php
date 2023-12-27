<?php

namespace App\Http\Controllers\API;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\PostCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\AppBaseController;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProfileController extends AppBaseController
{

    public function __construct(){

        $this->middleware(['auth:api','verified']);

    }




    public function get_profile($userId){
        $user = User::find($userId);
        return $this->json_custom_response(["user" => new UserResource($user)]);
    }

    public function get_posts($userId){
        $user = User::find($userId);

        $posts = Post::where('user_id',$user->id)->paginate(10);

          return $this->json_custom_response(new PostCollection($posts));
    }



    public function update_profile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'=>'required|string',
            'bio'=>'string',
            'email'=>'required|unique:users,email,'.Auth::id(),
        ]);
        if ($validator->fails()) {

            throw new HttpResponseException(response()->json([
                'errors' => $validator->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));

        }
        if ($validator->passes()){
            $user = User::query()->where('id',Auth::id())->first();
            $user ->update([
                'name'=>$request->name,
                'email'=>$request->email,
                'bio' => $request->bio,
            ]);


            return $this->sendResponse(["user" => new UserResource($user)], 'operation accomplished successfully');
        }

    }



    public function update_photo( Request $request){
        $validator = Validator::make($request->all(), [
            'photo'=>'required',

        ]);
        if ($validator->fails()) {

            throw new HttpResponseException(response()->json([
                'errors' => $validator->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));


        }
        if ($validator->passes()){
            $user= User::find(Auth::id());
            $data = $request->all();
            DB::beginTransaction();
            try{
                if ($request->hasfile('photo') ) {
                    $image_user = $request->file('photo');
                    $image_name = url('').'/uploads/users/'.time().'.' .$image_user->getClientOriginalExtension();
                  if($image_user->move(public_path('uploads/users/'), $image_name)){
                    // delete old img
                      $imagePath = Str::after($user->photo, url(url('').'/'));

                        if(File::exists($imagePath) && $user->photo != 'http://127.0.0.1:8000/uploads/users/default.jpg')
                        {
                            File::delete($imagePath);
                        }

                    $data['photo'] = $image_name;
                  }
                } else {
                    unset($data['photo']);
                }
                $user->update($data);

                DB::commit();
                return $this->sendResponse(["user" => new UserResource($user)], 'operation accomplished successfully');

            }
            catch (Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }


    }


     public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $user = User::find(auth()->id());

        if (!Hash::check($request->input('current_password'), $user->password)) {

            return $this->sendError('Current password is incorrect.',422);

        }

        $user->password = bcrypt($request->input('new_password'));
        $user->save();

        return $this->sendSuccess('Password changed successfully');

    }


    public function delete_account(){
        $user =auth()->user();
        $user->delete();
        return $this->sendSuccess('Account deleted successfully');
    }

    public function follow($userId)
    {
        try {

            $user= User::findOrFail($userId);

        if (Auth::user()->id === $user->id) {
            return $this->sendError('You cannot follow yourself.',422);
        }

        if (!Auth::user()->isFollowing($user)) {

            Auth::user()->follow($user);
            return $this->sendResponse(["isFollowing" => !Auth::user()->isFollowing($user)], 'You are now following '  . $user->name);
           // return $this->sendSuccess('You are now following '  . $user->name);

        }else{
                Auth::user()->unfollow($user);
                return $this->sendResponse(["isFollowing" => !Auth::user()->isFollowing($user)], 'You have unfollowed ' . $user->name);
              //  return $this->sendSuccess('You have unfollowed ' . $user->name);

        }

        } catch (\Throwable $th) {
                 return $this->sendError('User not found.');
        }


    }


     public function getFollowers($userId)
    {

        try {
            $user = User::findOrFail($userId);
            $followers = $user->followers()->select('users.id','name','photo')->get();

            return response()->json(['followers' => $followers]);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('User not found.');
        }

    }

    public function getfollowings($userId)
    {
        try {

            $user = User::findOrFail($userId);
            $followers = $user->followings()->select('users.id','name','photo')->get();

            return response()->json(['followers' => $followers]);

        } catch (ModelNotFoundException $e) {
            return $this->sendError('User not found.');
        }


    }



}
