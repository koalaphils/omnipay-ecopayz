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

// users
$router->post('/api/users', 'UserController@create');
$router->post('/api/users/login', 'UserController@login');
$router->post('/api/users/refresh', 'UserController@refresh');
$router->post('/api/users/forgot-password', 'UserController@forgotPassword');

// sms-code
$router->post('/api/sms-code/get-code', 'SmsCodeController@getSmsCode');
$router->get('/api/sms-code/get-code-get', 'SmsCodeController@getSmsCodeTest');
$router->get('/api/sms-code/get-code', 'SmsCodeController@sendCode');

// countries
$router->get('/api/countries', 'CountryController@selectCountry');

// transaction
$router->post('/api/transaction/list', 'TransactionController@getTransactionList');
$router->post('/api/transaction/deposit', 'TransactionController@deposit');
$router->post('/api/transaction/withdraw', 'TransactionController@withdraw');
$router->get('/api/transaction/exchange-rates', 'TransactionController@getExchangeRates');