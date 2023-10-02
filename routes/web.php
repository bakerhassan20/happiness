<?php

use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

/* Route::get('/', function () {

   // Cache::forget('mobile_'.$request->mobile);
   // $login =  $request->all();
   // $admin = User::query()->where(['mobile'=>$request->mobile])->where('deleted',0)->first();
    $code = mt_rand(1000,9000);

    $curl = curl_init();
    curl_setopt_array($curl, array(
                  CURLOPT_URL => 'http://hotsms.ps/sendbulksms.php?user_name=Gtack&user_pass=7054467&sender=Gtack&mobile='. +201122002942 .'&type=0&text=مرحبا%20بك%20في%20مزادي%20رقم%20الكود'. '-'.$code ,
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
                if(curl_errno($curl))
                {
                    echo 'Curl error: ' . curl_error($curl);

                }
                    curl_close($curl);

       return $response;




});
 */


Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');



