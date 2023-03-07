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

$router->group(["prefix" => "api"],function() use($router){
    $router->group(["prefix" => "v1"],function() use($router){
        $router->post('setwebhook',[
            'middleware' => 'user',
            'uses' => "MessangerController@setWebhook"
        ] );
        $router->post('webhook', "MessangerController@webhook");
        $router->post('sendmessage/{id}',[
            'middleware' => 'user',
            'uses' => "MessangerController@sendMessage"
        ] );
        $router->post('subscription/{id}/{status}', [
            'middleware' => 'user',
            'uses' => 'MessangerController@subscriptionStatus'
        ]);
    });
});



