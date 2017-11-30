<?php
	
$api = app('Dingo\Api\Routing\Router');

$api->version('v1', function ($api) {

	$api->post('auth/login', 'App\Api\V1\Controllers\AuthController@login');

	// example of protected route
	$api->group(['middleware' => 'api.auth'], function ($api) {		
		//Route::post('awscommand', 'AWSController@command');
		$api->post('command', 'App\Api\V1\Controllers\AWSController@command');
		$api->post('featuredit', 'App\Api\V1\Controllers\AWSController@featureEdit');
		$api->post('awsfeature', 'App\Api\V1\Controllers\AWSController@feature');
		$api->post('scriptlang', 'App\Api\V1\Controllers\AWSController@scriptLang');
    });

});
