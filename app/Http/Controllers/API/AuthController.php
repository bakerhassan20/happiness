<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\UserService;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\LoginResource;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Validator;

class AuthController extends AppBaseController
{
    protected $userService;


    public function __construct(UserService $userSE)
    {


        $this->userService = $userSE;
        $this->middleware(['auth:api'],['except'=>['Login','Register','VerifyEmail','auth','refresh']]);
       // $this->middleware('verified',['except'=>['Register']]);


    }

     private function auth($user)
    {
        $token = auth()->login($user);
        $message ='User login successfully';
        $expires_in = auth()->factory()->getTTL() * 60;

        return $this->sendResponse([
            "user" => new UserResource($user),
            "access_token"=>$token,
            'expires_in' => $expires_in,
            ],$message);
    }


    public function refresh(){
        $current_token  = JWTAuth::getToken();
        $token          = JWTAuth::refresh($current_token);
        $message ='token refresh successfully';
        return $this->sendResponse(["token" => $token],$message);
    }




    public function Register(RegisterRequest $request)
    {
        $input = $request->all();
        $input['photo'] = url('').'/uploads/users/default.jpg';
        $user = User::create($input);

        if(!$user->third_party){
            $this->userService->verifyCode($user);
        }
        else{
            return $this->auth($user);
        }

        return $this->sendResponse(["user" => new UserResource($user)], 'Code Send successfully');


    }

    public function Login(LoginRequest $request){


        $email = $request->email;
        $password = $request->password;

        if(isset($request->third_party) && $request->third_party == 1){
            $user= User::where('email', $request->email)->first();
            return $this->auth($user);
        }

        if(!auth()->attempt($request->all())){

            $errors = 'Email Or Password Incorect';
            return $this->sendError($errors);
        }
            $user=User::where('email',$email)->first();

            if($user->email_verified_at == null){

                $this->userService->verifyCode($user);
                return $this->sendResponse(["user" => new UserResource($user)], 'Verify Your Email ,Code Send successfully' );
            }
            return $this->auth($user);

    }

    public function VerifyEmail(Request $request)
    {
        if (empty($request->code)) {
            return $this->sendError('code is required');
        }
        if (empty($request->email)) {
            return $this->sendError('email is required');
        }
        $user = User::where([ ['code', $request->code], ['email',$request->email]])->first();
        if (!$user) {
            return $this->sendError('Wrong code or email.');

        }

        $user->markEmailAsVerified();
        $user->code=null;
        $user->save();
        return $this->auth($user);
    }



    public function logout(Request $request)
    {
        if(Auth::user()){
            Auth::user()->token()->revoke();
            return $this->sendSuccess('Successfully logged out');
        }



    }


}
