<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\AppBaseController;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProfileController extends AppBaseController
{

    public function __construct(){

        $this->middleware(['auth:api','verified']);

    }




    public function get_profile(Request $request){
        $user = User::find('id',Auth::id());

        return $this->sendResponse(["user" => new UserResource($user)], 'User Information');
    }



    public function update_profile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'=>'required',
            'city_id'=>'required',
            'email'=>'required|unique:users,email,'.Auth::id(),
            // 'mobile'=>'required|unique:users,mobile,'.Auth::id(),

        ]);
        if ($validator->fails()) {

            throw new HttpResponseException(response()->json([
                'errors' => $validator->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));

        }
        if ($validator->passes()){
            $data = $request->all();
            $user = User::query()->where('id',Auth::id())->first();
            $user ->update([
                'name'=>$request->name,
                'email'=>$request->email,
                // 'mobile'=>$request->mobile,
                'city_id' => $request->city_id,
            ]);
            $customer=Customer::query()->where('user_id',Auth::id())->update($data);
            return response([
                'status'=>true,
                'message_en'=>'operation accomplished successfully',
                'message_ar'=>'تمت العملية بنجاح',
                'data'=>$user,
                'code'=>200
            ]);
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



    public function follow($userId)
    {
        try {

            $user= User::findOrFail($userId);

        if (Auth::user()->id === $user->id) {
            return $this->sendError('You cannot follow yourself.');
        }

        if (!Auth::user()->isFollowing($user)) {

            Auth::user()->follow($user);
            return $this->sendSuccess('You are now following '  . $user->name);

        }else{
                Auth::user()->unfollow($user);
                return $this->sendSuccess('You have unfollowed ' . $user->name);

        }

        } catch (\Throwable $th) {
                 return $this->sendError('User not found.');
        }


    }



}
