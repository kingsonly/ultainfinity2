<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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

$router->group(["prefix" => "api"], function () use ($router) {
    $router->group(["prefix" => "v1"], function () use ($router) {
        $router->post('telegram/setwebhook', [
            'middleware' => 'user',
            'uses' => "TelegramController@setWebhook",
        ]);
        $router->post('telegram/webhook', "TelegramController@webhook");
        $router->post('telegram/sendmessage/{id}', [
            'middleware' => 'user',
            'uses' => "TelegramController@sendMessage",
        ]);
        $router->post('telegram/subscription/{id}/{status}', [
            'middleware' => 'user',
            'uses' => 'TelegramController@subscriptionStatus',
        ]);
        // route for twitter messages
        $router->post('setwebhook', [
            'middleware' => 'user',
            'uses' => "TwitterController@setWebhook",
        ]);
        $router->post('webhook', "TwitterController@webhook");
        $router->post('sendmessage/{id}', [
            'middleware' => 'user',
            'uses' => "TwitterController@sendMessage",
        ]);
        $router->post('subscription/{id}/{status}', [
            'middleware' => 'user',
            'uses' => 'TwitterController@subscriptionStatus',
        ]);

    });
});
