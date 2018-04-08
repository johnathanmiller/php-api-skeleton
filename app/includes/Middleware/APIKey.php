<?php

namespace App\Middleware;

class APIKey {

	protected $apiKey;

	public function __construct($apiKey) {
		$this->apiKey = $apiKey;
	}

	public function __invoke($request, $response, $next) {

		if ($request->hasHeader('API-Key') && $request->getHeaderLine('API-Key') === $this->apiKey) {
			$response = $next($request, $response);

		} else {
			$response = $response->withJson([
				'error' => [
					'message' => 'Unauthorized'
				]
			], 401);
		}

		return $response;

	}

}