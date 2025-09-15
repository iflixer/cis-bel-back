<?php

/*
dump($_SERVER['SERVER_NAME']);
DB::listen(function($query) {
    var_dump($query->sql, $query->bindings);
});
*/

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

// Сайт на Laravel
//Route::get('/', function () {
//    abort(404);
//    // return view('index');
//});
/*Route::group(['middleware'=>['auth','center'] ], function () {
    Route::get('/home/{key}', ['uses'=>'VideoController@activated']);
    Route::get('/home', ['as'=>'home','uses'=>'VideoController@show']);
    Route::get('/video', ['as'=>'video','uses'=>'VideoController@showVideo']);
    Route::get('/player', ['as'=>'player','uses'=>'PlayerController@show']);
    Route::get('/tikets', ['as'=>'tikets','uses'=>'TiketsController@show']);
    Route::get('/users', ['as'=>'tikets','uses'=>'UsersController@show']);
    // Route::get('/pages', ['as'=>'pages','uses'=>'VideoController@pages']);
    // Route::get('/pages/{page}', ['as'=>'page','uses'=>'VideoController@page']);
    Route::get('/cabinet', ['as'=>'cabinet','uses'=>'СabinetController@show']);
    Route::post('/cabinet', ['uses'=>'СabinetController@passUpdate']);
});*/
Route::auth();
// --------



// api общего доступа
Route::match(['get','post'],'/api/{method}', ['middleware'=>['throttleCustom'], 'uses'=>'ApiController@start']);






// Отображение плеера в фрейме
// Route::get('/show/{id}', ['middleware'=>['showMiddleware'], 'uses'=>'ShowController@newshow'])->where('id', '[0-9]+');
Route::get('/show/{id}', ['middleware'=>['showMiddleware'], 'uses'=>'ShowController@player'])->where('id', '[0-9]+');
Route::get('/show/{type}/{id}', ['middleware'=>['showMiddleware'], 'uses'=>'ShowController@player'])->where('id', 'kinopoisk|imdb')->where('id', '[a-z0-9]+');
Route::get('/share/{id}', ['uses'=>'ShowController@share'])->where('id', '[0-9]+');
// для васта
Route::get('/share/{id}', ['uses'=>'ShowController@share'])->where('id', '[0-9]+');
// Route::get('/newshow/{id}', ['middleware'=>['showMiddleware'], 'uses'=>'ShowController@newshow'])->where('id', '[0-9]+');

// api для плеера
Route::match(['get','post'],'/apishow/{method}', ['middleware'=>['apiShowMiddleware'], 'uses'=>'ApiController@start']);

// отчеты network-stater о загруженности сети и живости CND ноды
Route::post('/cdn/netload', 'CdnController@netload');




// Доступ к api с фронт части , 'throttleCustom'
Route::match(['get','post'],'/front/{method}', ['middleware'=>['frontMiddleware', 'userActionLogging'], 'uses'=>'ApiController@start']);

// api авторизация
Route::group(['prefix' => '/oauth' ], function () {
    Route::post('/registr', [ 'uses'=>'AuthController@registr']);
    Route::post('/forgot-password', [ 'uses'=>'AuthController@forgotPassword']);
    Route::post('/reset-password', [ 'uses'=>'AuthController@resetPassword']);
    Route::post('/login', [ 'uses'=>'AuthController@login']);
    Route::post('/token', [ 'uses'=>'AuthController@token']);
    Route::post('/exits', [ 'middleware'=>['frontMiddleware'], 'uses'=>'AuthController@exits']);
});









// api парсера
Route::get('/parse', ['middleware'=>[], 'uses'=>'ApiController@parse']);




Route::get('/cronjob/videodb', ['middleware' => [], 'uses' => 'CronjobController@videodb']);



// test
// Route::get('/test/episode/{id}', ['middleware' => [], 'uses' => 'TestController@episode'])->where('id', '[0-9]+');
Route::get('/test/translations', ['middleware' => [], 'uses' => 'TestController@translations']);
// Route::get('/test/setTranslationsInTableFiles', ['middleware' => [], 'uses' => 'TestController@setTranslationsInTableFiles']);
Route::get('/test/import_kinopoisk', ['middleware' => [], 'uses' => 'TestController@importKinoPoisk']);
Route::get('/test/import_kinopoisk_only_imdb', ['middleware' => [], 'uses' => 'TestController@importKinoPoiskOnlyImdb']);
Route::get('/test/import_tmdb', ['middleware' => [], 'uses' => 'TestController@importTmdb']);
Route::get('/test/import_fanart', ['middleware' => [], 'uses' => 'TestController@importFanart']);
Route::get('/test/import_openai', ['middleware' => [], 'uses' => 'TestController@importOpenai']);
Route::get('/test/import_thetvdb', ['middleware' => [], 'uses' => 'TestController@importThetvdb']);
// Route::get('/test/player', ['middleware' => [], 'uses' => 'TestController@player']);
// Route::get('/test/restore/index', ['middleware' => [], 'uses' => 'TestController@restoreIndex']);
// Route::get('/test/restore/{id}', ['middleware' => [], 'uses' => 'TestController@restore'])->where('id', '[0-9]+');
// Route::get('/show2/{id}', ['middleware'=>[], 'uses'=>'ShowController@player2'])->where('id', '[0-9]+');
// Route::get('/test/export', ['middleware' => [], 'uses' => 'TestController@export']);
// Route::get('/test/filterNetworkTraffic', ['middleware' => [], 'uses' => 'TestController@filterNetworkTraffic']);