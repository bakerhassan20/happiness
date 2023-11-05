<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\PostCollection;
use App\Http\Resources\UserCollection;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\AppBaseController;
use Illuminate\Http\Exceptions\HttpResponseException;

class PublicController extends AppBaseController
{

    public function __construct(){

        $this->middleware(['auth:api','verified']);

    }


    public function Filter(Request $request){

        $validator = Validator::make($request->all(), [
            'name'=>'required',
        ]);
        if ($validator->fails()) {

            throw new HttpResponseException(response()->json([
                'errors' => $validator->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));

        }

        $name = $request->input('name');
        $users = User::when($name, function ($query, $name) {
            return $query->where('name', 'like', "%$name%");
        })->get();

        return $this->sendResponse(["user" => new UserCollection($users)], 'filter');

    }



    public function favorite(){
        $user = Auth::user();
        $user = User::find($user->id);
        $posts =  $user->favorites()->paginate(10);
        return $this->json_custom_response(new PostCollection($posts));

    }


}
