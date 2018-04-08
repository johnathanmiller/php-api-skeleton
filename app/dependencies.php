<?php

use App\Controllers\User;
use App\Controllers\OAuth2;
use App\Middleware\APIKey;
use App\Middleware\OAuth2Auth;
use App\Middleware\RateLimiter;
use App\Storage\Database;
use App\Storage\OAuth2Storage;
use App\Storage\Redis;

$container = $app->getContainer();

$container['User'] = function($container) {
	return new User($container);
};

$container['APIKey'] = function($container) {
	return new APIKey($container->get('settings')['apiKey']);
};

$container['Database'] = function($container) {
	return new Database(
		$container->get('settings')['mysql']['host'] .';port='. $container->get('settings')['mysql']['port'],
		$container->get('settings')['mysql']['username'],
		$container->get('settings')['mysql']['password'],
		$container->get('settings')['mysql']['database']['api']
	);
};

$container['Redis'] = function($container) {
	return new Redis(
		$container->get('settings')['redis']['scheme'],
		$container->get('settings')['redis']['host'],
		$container->get('settings')['redis']['port']
	);
};

$container['OAuth2Database'] = function($container) {
	return new Database(
		$container->get('settings')['mysql']['host'] .';port='. $container->get('settings')['mysql']['port'],
		$container->get('settings')['mysql']['username'],
		$container->get('settings')['mysql']['password'],
		$container->get('settings')['mysql']['database']['oauth']
	);
};

$container['OAuth2Server'] = function($container) {

	$storage = new OAuth2Storage($container);
	$server = new \OAuth2\Server($storage, [
		'access_lifetime' => 3600 * 24 // 1 day
	]);
	$server->addGrantType(new \OAuth2\GrantType\UserCredentials($storage));
	$server->addGrantType(new \OAuth2\GrantType\RefreshToken($storage, [
		'always_issue_new_refresh_token' => true,
		'refresh_token_lifetime' => 3600 * 24 * 3 // 3 days
	]));

	return $server;

};

$container['OAuth2'] = function($container) {
	return new OAuth2($container);
};

$container['OAuth2Auth'] = function($container) {
	return new OAuth2Auth($container);
};

$container['RateLimiter'] = function($container) {
	return new RateLimiter($container);
};