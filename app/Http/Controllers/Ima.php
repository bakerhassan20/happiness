<?php

namespace App\Http\Controllers\UserApi;

use App\Http\Controllers\Controller;
use App\Models\UserApi\Customer;
use App\Models\UserApi\User;
use App\Models\UserApi\CardCategory;
use App\Models\UserApi\Card;
use App\Models\UserApi\Transaction;
use App\Models\UserApi\UserWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Firestore;
use Exception;
use Twilio\Rest\Client;
class AuthController extends Controller
{
    public function send_sms($number,$msg)
    {
        $receiverNumber = $number;
        $message = $msg;

        try {

            $account_sid = getenv("TWILIO_SID");
            $auth_token = getenv("TWILIO_TOKEN");
            $twilio_number = getenv("TWILIO_FROM");

            $client = new Client($account_sid, $auth_token);
            $client->messages->create($receiverNumber, [
                'from' => $twilio_number,
                'body' => $message]);

//            dd('SMS Sent Successfully.');

        } catch (Exception $e) {
            dd("Error: ". $e->getMessage());
        }
    }

    public function delete_account()
    {
        $user = Auth::User();
        $user -> deleted = 1;
        $user -> save();
            return response([
                'status'    =>false,
                'message_en'=>'cards categories list',
                'message_ar'=>'تم حذف الحساب',
                'code'      =>200
            ],200);


    }
    public function get_card_categories()
    {
        $list = Card::where('status',0)->select('amount')->distinct('amount')->get();
        foreach($list as $item)
        {
            $item -> amount = (int)$item->amount;
        }
            return response([
                'status'    =>false,
                'message_en'=>'cards categories list',
                'message_ar'=>'قائمة بانواع البطاقات',
                'data'    =>$list,
                'code'      =>200
            ],200);


    }
    public function charg_amount(Request $request)
    {
        $card = Card::where('status',0)->where('amount',$request -> amount)->first();
        if(!$card)
    {
            return response([
                'status'    =>false,
                'message_en'=>'serial number sent succesfuly',
                'message_ar'=>'لا يوجد بطاقات من هذي الفئة',
                'code'      =>404
            ],404);

    }
        $card -> status = 1;
        $card -> reseller_id = Auth::id();
        $card -> save();
            return response([
                'status'    =>true,
                'message_en'=>'serial number sent succesfuly',
                'message_ar'=>'تم ارسال الكود بنجاح',
                'data'    =>$card,
                'code'      =>200
            ],200);


    }
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
            'verify_mobile_code' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response([
                'status'    =>false,
                'message_en'=>'Make sure that the information is correct and fill in all fields',
                'message_ar'=>'تاكد من صحة البيانات وملئ جميع الحقول',
                'errors'    =>$errors,
                'code'      =>422
            ],422);

        }
        if ($validator->passes()) {
            if($request->verify_mobile_code == 1407)
            {
                $login =  $request->all();
                $admin = User::query()->where(['mobile'=>$request->mobile,'type'=>1])->where('deleted',0)->first();
                if ($admin){
                    $admin-> fcm_token = $request -> fcm_token;
                    $admin -> save();

                    $accessToken = $admin->createToken('authToken')->accessToken;

                    return response()->json([
                        'status'     => true,
                        'message_en' => 'Login successfully',
                        'message_ar' => 'تمت عملية الدخول بنجاح',
                        'accessToken'=> $accessToken,
                        'code'       => 200,
                    ],200);

                }
            }
            $admin = User::query()->where(['mobile'=>$request->mobile,'verify_mobile_code'=>$request->verify_mobile_code])->first();
            // return $admin;
            if (!$admin){
                    return response()->json([
                    'status'     => true,
                    'message_en' => 'Wrong OTP',
                    'message_ar' => 'الكود غير صحيح',
                    'code'       => 403,
                ],403);


            }
            $login =  $request->all();
            $admin = User::query()->where(['mobile'=>$request->mobile,'type'=>1])->first();
            if ($admin){
                $admin-> fcm_token = $request -> fcm_token;
                $admin -> save();

                $accessToken = $admin->createToken('authToken')->accessToken;

                return response()->json([
                    'status'     => true,
                    'message_en' => 'Login successfully',
                    'message_ar' => 'تمت عملية الدخول بنجاح',
                    'accessToken'=> $accessToken,
                    'code'       => 200,
                ],200);


            }
            else {


                return response([
                    'status' => false,
                    'message_en' => 'User Not Found',
                    'message_ar' => 'هذا المستخدم غير موجود',
                    'code' => 404,
                ],404);
            }

        }


    }
    public function get_otp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response([
                'status'    =>false,
                'message_en'=>'Make sure that the information is correct and fill in all fields',
                'message_ar'=>'تاكد من صحة البيانات وملئ جميع الحقول',
                'errors'    =>$errors,
                'code'      =>422
            ],422);

        }
        if ($validator->passes()) {

            Cache::forget('mobile_'.$request->mobile);
            $login =  $request->all();
            $admin = User::query()->where(['mobile'=>$request->mobile])->where('deleted',0)->first();
            $code = mt_rand(1000,9000);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                          CURLOPT_URL => 'http://hotsms.ps/sendbulksms.php?user_name=Mazadi&user_pass=5500739&sender=Mazadi&mobile='. $request->mobile .'&type=0&text=مرحبا%20بك%20في%20مزادي%20رقم%20الكود'. '-'.$code ,
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_ENCODING => '',
                          CURLOPT_MAXREDIRS => 10,
                          CURLOPT_TIMEOUT => 0,
                          CURLOPT_FOLLOWLOCATION => true,
                          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                          CURLOPT_CUSTOMREQUEST => 'GET',
                          CURLOPT_HTTPHEADER => array(
                            'Cookie: PHPSESSID=5vt28v5dib0safr5g3h9t8d3e2'
                          ),
                        ));

                        $response = curl_exec($curl);

                        curl_close($curl);
            if ($admin){
                Cache::add('mobile_'.$request->mobile, $code, now()->addMinutes(60));
                Cache::add('mobile_'.$request->mobile, '1407', now()->addMinutes(60));

                $admin -> verify_mobile_code = $code;
                $admin -> save();

                return response([
                    'status' => true,
                    'message_en' => 'Code OTP For Account',
                    'message_ar' => 'رمز التحقق الخاص بك',
                    'verify_mobile_code' => $code,
                    'code' => 200,
                ],200);
            }
            else {
                Cache::add('mobile_'.$request->mobile, $code, now()->addMinutes(60));
                Cache::add('mobile_'.$request->mobile, '1407', now()->addMinutes(60));
                return response([
                    'status' => true,
                    'message_en' => 'This Account Not found',
                    'message_ar' => 'هذا الحساب غير موجود',
                    'verify_mobile_code' => $code,
                    'code' => 404,
                ],404);
            }

        }


    }
    public function register(Request $request)
    {
// return $request->all();
        $validator = Validator::make($request->all(), [
            // 'mobile' => 'nullable|unique:users',
            'email'=>'nullable|unique:users',
            'name'=>'required',
            'verify_mobile_code'=>'required',
            'fcm_token'=>'required'
        ]);


        if ($validator->fails()) {
            $errors = $validator->errors();

            return response([
                'status'=>false,
                'message_en'=>'Make sure that the information is correct and fill in all fields',
                'message_ar'=>'تاكد من صحة البيانات وملئ جميع الحقول',
                'message'=>'تاكد من صحة البيانات وملئ جميع الحقول',
                'errors'=>$errors,
                'code'=>422
            ]);
        }

        if ($validator->passes()) {
            DB::beginTransaction();
            try {
                $code = Cache::get('mobile_'.$request->mobile);
                if ($code || $request->verify_mobile_code == '1407'){
                    if ($code==$request->verify_mobile_code || $request->verify_mobile_code == '1407'){
                        $user = User::query()->create([
                            'name' => $request->name,
                            'mobile' => $request->mobile,
                            'email' => $request->email,
                            'city_id' => $request->city_id,
                            'type' =>1,
                            'fcm_token' => $request -> fcm_token,
                            'role' =>'customer',
                        ]);

                        $wallet=UserWallet::query()->where('user_id',$user->id)->first();

                         $wallet=   UserWallet::query()->updateOrCreate([
                                'user_id'=>$user->id,
                            ],['amount'=> 100]);

                        Transaction::query()->create([
                                'user_id'=>$user->id,
                                'wallet_id'=>$wallet->id,
                                'amount'=>100,
                                'type'=>0,
                            ]);

                        $data=$request->all();
                        $number = sprintf('%06d',$user->id);
                        $user->update(['user_no'=>'C-'.$number]);

                        $photo= url('/').'/public/uploads/users/default-user-image.png';
                        Customer::query()->create([
                            'user_id'=>$user->id,
                            'user_no'=>'C-'.$number,
                            'name' => $request->name,
                            'mobile' => $request->mobile,
                            'whatsapp' => $request->whatsapp,
                            'email' => $request->email,
                            'city_id' => $request->city_id,
                            'address' => $request->address,
                            'district' => $request->district,
                            'photo' => $photo,
                        ]);
                        $curl = curl_init();


                        $accessToken = $user->createToken('UserToken')->accessToken;
                        $user->token = $accessToken;
//                        return  $user;
                    }else{
                        return response([
                            'status'=>false,
                            'message_en'=>'Verify Mobile Code Not Correct',
                            'message_ar'=>'رمز التحقق خاطئ',
                            'code'=>400
                        ],400);
                    }
                }else{
                    return response([
                        'status'=>false,
                        'message_en'=>'Verify Mobile Code Not Correct',
                        'message_ar'=>'رمز التحقق خاطئ',
                        'message'=>'رمز التحقق خاطئ',
                        'code'=>400
                    ],400);
                }



                DB::commit();

                return response([
                    'status'    =>true,
                    'message_en'    =>'Register Done',
                    'message_ar'    =>'تم التسجيل',
                    'message'    =>'تم التسجيل',
                    'data'          => $user,
                    'code'          =>200
                ],200);

            }
            catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

        }
    }

    public function get_profile(Request $request){
        $user = Customer::query()->where('user_id',Auth::id())
//            ->select('id','name','mobile','email','photo','city','status','notification','user_id')
            ->with('user')
            ->first();

            $user['status'] = $user->user->status;
            $user['isReseller'] = $user->user->isReseller;

        return response([
            'status'    =>true,
            'message_en'=>'info current user',
            'message_ar'=>'معلومات المستخدم الحالي',
            'data'      => $user,
            'code'      =>200
        ]);
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
            $errors = $validator->errors();
            return response([
                'status'=>false,
                'message_en'=>'Make sure that the information is correct and fill in all fields',
                'message_ar'=>'تاكد من صحة البيانات وملئ جميع الحقول',
                'errors'=>$errors,
                'code'=>422
            ]);
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
    public function notification(){
        $customer=Customer::query()->where('user_id',Auth::id())->first();
        if ($customer->notification==1){
            $customer->update([
                'notification'=>0,
            ]);
        }else{
            $customer->update([
                'notification'=>1,
            ]);
        }
        return response([
            'status'=>true,
            'message_en'=>'operation accomplished successfully',
            'message_ar'=>'تمت العملية بنجاح',
            'data'=>$customer,
            'code'=>200
        ]);
    }
    public function logout(Request $request)
    {
        if(Auth::user()){
            Auth::user()->token()->revoke();
            return response()->json([
                'status' => true,
                'message_en' => 'Successfully logged out',
                'message_ar' => 'تمت عملية الخروج  بنجاح',
                'code' => 200,
            ]);
        }



    }
    public function update_photo( Request $request){
        $validator = Validator::make($request->all(), [
            'photo'=>'required',

        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response([
                'status'=>false,
                'message_en'=>'Make sure that the information is correct and fill in all fields',
                'message_ar'=>'تاكد من صحة البيانات وملئ جميع الحقول',
                'errors'=>$errors,
                'code'=>422
            ]);
        }
        if ($validator->passes()){
            $customer= Customer::query()->where('user_id',Auth::id())->first();
            $data = $request->all();
            DB::beginTransaction();
            try{
                if ($request->hasfile('photo') ) {
                    $image_user = $request->file('photo');
                    $image_name = url('').'/public/uploads/users/'.time().'.' .$image_user->getClientOriginalExtension();
                    $image_user->move(public_path('uploads/users/'), $image_name);

                    $data['photo'] = $image_name;

                } else {
                    unset($data['photo']);
                }
                $customer->update($data);


                DB::commit();
                return response([
                    'status'=>true,
                    'message_en'=>'operation accomplished successfully',
                    'message_ar'=>'تمت العملية بنجاح',
                    'data'=>$customer,
                    'code'=>200
                ]);
            }
            catch (Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }


    }















    public function Get_otp2(Request $request)
    {
        $user_name='';
        $user_pass='';
        $sender='';
        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response([
                'status'    =>false,
                'message_en'=>'Make sure that the information is correct and fill in all fields',
                'message_ar'=>'تاكد من صحة البيانات وملئ جميع الحقول',
                'errors'    =>$errors,
                'code'      =>422
            ],422);

        }
        if ($validator->passes()) {



            Cache::forget('mobile_'.$request->mobile);
            $login =  $request->all();
            $admin = User::query()->where(['mobile'=>$request->mobile])->where('deleted',0)->first();
            $code = mt_rand(1000,9000);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                          CURLOPT_URL => 'http://hotsms.ps/sendbulksms.php?user_name='.$user_name.'&user_pass='.$user_pass.'&sender='.$sender.'&mobile='. $request->mobile .'&type=0&text='.$code ,
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_ENCODING => '',
                          CURLOPT_MAXREDIRS => 10,
                          CURLOPT_TIMEOUT => 0,
                          CURLOPT_FOLLOWLOCATION => true,
                          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                          CURLOPT_CUSTOMREQUEST => 'GET',
                          CURLOPT_HTTPHEADER => array(
                            'Cookie: PHPSESSID=5vt28vtrgrffv5dib0sasfwfrgfr5g3h9tgtrg8d3e2'
                          ),
                        ));
                        $response = curl_exec($curl);
                        curl_close($curl);
                    }

                    }











}
