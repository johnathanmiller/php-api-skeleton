<?php

namespace App\Controllers;

class OAuth2 {

	public function __construct($container) {
		$this->container = $container;
	}

	public function token($request, $response, $args) {

		$symfony_request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
		$request = \OAuth2\HttpFoundationBridge\Request::createFromRequest($symfony_request);

		$token = $this->container->get('OAuth2Server')->handleTokenRequest($request);

		$oauth_response = (array) json_decode($token->getResponseBody());

		if ($token->isSuccessful()) {
			$new_response = $response->withJson([
				'data' => $oauth_response
			]);

		} else {
			$new_response = $response->withJson([
				'error' => [
					'type' => $oauth_response['error'],
					'message' => $oauth_response['error_description']
				]
			], 400);
		}

		return $new_response;

	}

	public function validate($request) {

		$symfony_request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
		$request = \OAuth2\HttpFoundationBridge\Request::createFromRequest($symfony_request);

		if (!$this->container->get('OAuth2Server')->verifyResourceRequest($request)) {
			return false;
		}

		return true;

	}

}