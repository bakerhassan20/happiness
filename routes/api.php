<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PostsController;
use App\Http\Controllers\API\PublicController;
use App\Http\Controllers\API\ProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group(['middleware'=>'api','prefix'=>'auth'],function(){

    Route::post('/register',[AuthController::class,'Register']);
    Route::post('/verifyEmail',[AuthController::class,'VerifyEmail']);
    Route::post('/login',[AuthController::class,'Login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);


});

Route::group(['middleware'=>'api','prefix'=>'profile'],function(){

    Route::get('/{userId}', [ProfileController::class, 'get_profile']);
    Route::get('/{userId}/posts', [ProfileController::class, 'get_posts']);
    Route::post('/update-photo',[ProfileController::class,'update_photo']);
    Route::delete('/delete-account',[ProfileController::class,'delete_account']);

    Route::post('/follow/{userId}', [ProfileController::class, 'follow'])->name('follow');


});


Route::group(['middleware'=>'api','prefix'=>'post'],function(){

    Route::get('/', [PostsController::class, 'index']);
    Route::post('/create-post',[PostsController::class,'create']);
    Route::post('/update-post',[PostsController::class,'update']);
    Route::post('/delete-post',[PostsController::class,'delete']);
    Route::post('funny/{postId}',[PostsController::class, 'Funny_Post']);
    Route::post('add-remove-favorites/{postId}', [PostsController::class, 'add_Remove_Favorites']);
    Route::post('share/{postId}', [PostsController::class, 'sharePost']);


});

Route::group(['middleware'=>'api','prefix'=>'public'],function(){

    Route::post('/filter',[PublicController::class,'Filter']);
    Route::get('/favorite',[PublicController::class,'favorite']);


});
