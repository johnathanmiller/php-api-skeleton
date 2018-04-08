<?php

return [
	'settings' => [
		'determineRouteBeforeMiddleware' => false,
		'displayErrorDetails' => true,
		'apiKey' => 'somethingrandom', // Hardcoded for testing, should use a value that can be validated from a database
		'mysql' => [
			'host' => 'localhost',
			'port' => 3306,
			'username' => 'root',
			'password' => 'root',
			'database' => [
				'api' => 'api',
				'oauth' => 'oauth'
			]
		],
		'redis' => [
			'scheme' => 'tcp',
			'host' => '127.0.0.1',
			'port' => 6379
		],
		'rateLimit' => [
			'requests' => 12,
			'seconds' => 60
		]
	]
];