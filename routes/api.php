<?php

use Dingo\Api\Routing\Router;

/** @var Router $api */
$api = app(Router::class);

$api->version('v1', function (Router $api) {
    $api->group(['prefix' => 'auth'], function (Router $api) {
        $api->post('signup', 'App\\Api\\V1\\Controllers\\SignUpController@signUp');
        $api->post('login', 'App\\Api\\V1\\Controllers\\LoginController@login');

        $api->post('recovery', 'App\\Api\\V1\\Controllers\\ForgotPasswordController@sendResetEmail');
        $api->post('reset', 'App\\Api\\V1\\Controllers\\ResetPasswordController@resetPassword');

        $api->post('logout', 'App\\Api\\V1\\Controllers\\LogoutController@logout');
        $api->post('refresh', 'App\\Api\\V1\\Controllers\\RefreshController@refresh');
        $api->get('me', 'App\\Api\\V1\\Controllers\\UserController@me');
        $api->put('me', 'App\\Api\\V1\\Controllers\\UserController@updateMe');
    });

    $api->group(['prefix' => 'favorite', 'middleware' => 'jwt.auth'], function (Router $api) {
        $api->get('/', 'App\\Api\\V1\\Controllers\\FavoriteController@index');
        $api->post('/', 'App\\Api\\V1\\Controllers\\FavoriteController@store');
        $api->put('/{id}', 'App\\Api\\V1\\Controllers\\FavoriteController@update');
        $api->delete('/{id}', 'App\\Api\\V1\\Controllers\\FavoriteController@destroy');
    });

    $api->group(['prefix' => 'video', 'middleware' => 'jwt.auth'], function (Router $api) {
        $api->get('/', 'App\\Api\\V1\\Controllers\\VideoController@search');
    });

    $api->group(['middleware' => 'jwt.auth'], function (Router $api) {
        $api->get('protected', function () {
            return response()->json([
                'message' => 'Access to protected resources granted! You are seeing this text as you provided the token correctly.'
            ]);
        });

        $api->get('refresh', [
            'middleware' => 'jwt.refresh',
            function () {
                return response()->json([
                    'message' => 'By accessing this endpoint, you can refresh your access token at each request. Check out this response headers!'
                ]);
            }
        ]);
    });

    $api->get('hello', function () {
        return response()->json([
            'message' => 'This is a simple example of item returned by your APIs. Everyone can see it.'
        ]);
    });
});
