<?php

use App\Constant\PathName;
use App\Constant\UserRolesFixedData;
use App\OGC\EntityGetter\Observation;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OGC;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::group([
    //đặt route post kèm bảo mật cơ bản ở đây
    //middleware, namespace,...
//    'middleware'=>['ip.restrict']
    ],function (){

    //sensor
    Route::post(PathName::POST.'/'.Observation::PATH_VARIABLE_NAME,[OGC\PostController::class,"sensorPost"])
        ->middleware(['auth.rest:' . UserRolesFixedData::REST_SENSOR['id']]);
    Route::post(PathName::POST.'/'.PathName::CREATE_OBSERVATION,[OGC\PostController::class,"sensorPost"])
        ->middleware(['auth.rest:' . UserRolesFixedData::REST_SENSOR['id']]);


    //client
    Route::post(PathName::POST.'/{params?}',[OGC\PostController::class,"action"])
        ->where('params','^(?!.*' . Observation::PATH_VARIABLE_NAME . '(\(\d+\)(\/)?)?$).*$')
        ->middleware(['auth.rest:' . UserRolesFixedData::REST_CREATE['id']]);

    Route::post(PathName::BATCH,[OGC\BatchController::class,'action'])
        ->middleware('auth.rest:' . UserRolesFixedData::REST_BATCH['id']);

    Route::patch(PathName::PATCH.'/{params?}',[OGC\PatchController::class,'action'])
        ->where('params','^(?!.*' . Observation::PATH_VARIABLE_NAME . '(\(\d+\)(\/)?)?$).*$')
        ->middleware('auth.rest:' . UserRolesFixedData::REST_UPDATE['id']);

    Route::delete(PathName::DELETE.'/{params?}',[OGC\DeleteController::class,'action'])
        ->where('params','^(?!.*' . Observation::PATH_VARIABLE_NAME . '(\(\d+\)(\/)?)?$).*$')->middleware('auth.rest:' . UserRolesFixedData::REST_DELETE['id']);

    Route::get(PathName::GET.'/{params?}',[OGC\GetController::class,'action'])
        ->where('params','(.*)')
        ->middleware('auth.rest:' . UserRolesFixedData::REST_GET['id']);;
});


Route::get('login','App\Http\Controllers\UserController@login')->name('login');
Route::post('checklogin','App\Http\Controllers\UserController@checkLogin')->name('checkLogin');
Route::get('logout','App\Http\Controllers\UserController@logout')->name('logout');

Route::group([
                'namespace'=>'App\Http\Controllers',
                 'middleware' => ['auth','ip.restrict']
                    ],function (){

    Route::get('/dang-ky-tai-khoan',
        'UserController@getFormUser')
        ->name('user_register_form');
    Route::post('/dang-ky-tai-khoan-ket-qua',
        'UserController@registerUser')
        ->name('save_user');

    Route::get('/doi-mat-khau',
        'UserController@getFormChangePwd')
        ->name('changePassword');
    Route::post('/doi-mat-khau-ket-qua',
        'UserController@changePwd')
        ->name('changePwd');

    Route::get('/danh-sach-tai-khoan',
        'UserController@getUserList')
        ->name('userList');

    Route::get('/thay-doi-quyen',
        'UserController@changeRole')
        ->name('changeRole');
    Route::post('/thay-doi-quyen-ket-qua',
        'UserController@changeRoleResult')
        ->name('changeRoleResult');
    Route::get('/xoa-tai-khoan',
        'UserController@deleteUser')
        ->name('deleteUser');

    Route::get('/','Home@index')->name('index');
    Route::get('root','OGC\OgcRootController@action');



});
