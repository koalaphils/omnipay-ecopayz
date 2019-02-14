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
$router->post('/api/users/update-password', 'UserController@updatePassword');

// sms-code
$router->post('/api/sms-code/get-code', 'SmsCodeController@getSmsCode');
$router->get('/api/sms-code/get-code-get', 'SmsCodeController@getSmsCodeTest');
$router->get('/api/sms-code/get-code', 'SmsCodeController@sendCode');

// countries
$router->get('/api/countries', 'CountryController@selectCountry');

// transaction
$router->post('/api/transaction/list', 'TransactionController@getTransactionList');
$router->post('/api/transaction/deposit', 'TransactionController@deposit');
$router->post('/api/transaction/deposit-bitcoin', 'TransactionController@depositBitcoin');
$router->post('/api/transaction/bitcoin/{id}', 'TransactionController@getBitcoinTransaction');
$router->post('/api/transaction/withdraw', 'TransactionController@withdraw');
$router->post('/api/transaction/withdraw-bitcoin', 'TransactionController@withdrawBitcoin');
$router->get('/api/transaction/exchange-rates', 'TransactionController@getExchangeRates');
$router->post('/api/transaction/balance', 'TransactionController@getBalance');
$router->get('/api/transaction/bitcoin-rate', 'TransactionController@getBitcoinRate');
$router->post('/api/transaction/bitcoin-lock-rate', 'TransactionController@getBitcoinLockRate');

$router->post('/api/transaction/tickets', 'TransactionController@getTicketList');
$router->post('/api/transaction/tickets/conversation', 'TransactionController@getTicketConversation');
