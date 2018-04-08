<?php

namespace App\Middleware;

use App\Controllers\OAuth2;

class OAuth2Auth extends OAuth2 {

	public function __invoke($request, $response, $next) {

		if ($request->hasHeader('Authorization') && $this->validate($request)) {
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