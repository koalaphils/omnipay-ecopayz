<?php
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {    
    return view('index');
});

$router->post('/api/users', 'UserController@create');
$router->post('/api/users/login', 'UserController@login');
$router->post('/api/sms-code/get-code', 'SmsCodeController@getSmsCode');
$router->get('/api/sms-code/get-code-get', 'SmsCodeController@getSmsCodeTest');
$router->get('/api/sms-code/get-code', 'SmsCodeController@sendCode');
$router->get('/api/countries', 'CountryController@selectCountry');
$router->post('/api/transaction/deposit', 'TransactionController@deposit');