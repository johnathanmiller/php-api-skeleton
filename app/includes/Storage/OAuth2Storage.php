<?php

namespace App\Storage;

use \OAuth2\Storage\ClientCredentialsInterface;
use \OAuth2\Storage\UserCredentialsInterface;
use \OAuth2\Storage\AccessTokenInterface;
use \OAuth2\Storage\RefreshTokenInterface;

class OAuth2Storage implements ClientCredentialsInterface, UserCredentialsInterface, AccessTokenInterface, RefreshTokenInterface {

	private $_users = 'api_users';
	private $_clients = 'oauth_clients';
	private $_access_tokens = 'oauth_access_tokens';
	private $_refresh_tokens = 'oauth_refresh_tokens';

	public function __construct($container) {
		$this->container = $container;
	}

	/**
	 * Client Credentials Interface
	 */
	public function checkClientCredentials($client_id, $client_secret = null) {

		$this->container->get('OAuth2Database')->query("SELECT * FROM {$this->_clients} WHERE client_id = :client_id");
		$this->container->get('OAuth2Database')->bind(':client_id', $client_id);
		$client = $this->container->get('OAuth2Database')->single();

		return $client && $client['client_secret'] == $client_secret;

	}
	
	public function isPublicClient($client_id) {

		$this->container->get('OAuth2Database')->query("SELECT * FROM {$this->_clients} WHERE client_id = :client_id");
		$this->container->get('OAuth2Database')->bind(':client_id', $client_id);
		$client = $this->container->get('OAuth2Database')->single();

		return ($client) ? empty($client['client_secret']) : false;

	}

	/**
	 * Client Interface (extended from ClientCredentialsInterface)
	 */
	public function getClientDetails($client_id) {

		$this->container->get('OAuth2Database')->query("SELECT * FROM {$this->_clients} WHERE client_id = :client_id");
		$this->container->get('OAuth2Database')->bind(':client_id', $client_id);
		return $this->container->get('OAuth2Database')->single();

	}

	public function getClientScope($client_id) {

		if (!$clientDetails = $this->getClientDetails($client_id)) {
			return false;
		}

		if (isset($clientDetails['scope'])) {
			return $clientDetails['scope'];
		}

		return null;

	}

	public function checkRestrictedGrantType($client_id, $grant_type) {

		$details = $this->getClientDetails($client_id);

		if (isset($details['grant_types'])) {
			$grant_types = explode(' ', $details['grant_types']);

			return in_array($grant_type, (array) $grant_types);
		}

		return true;

	}

	/**
	 * User Credentials Interface
	 */
	public function checkUserCredentials($email, $password) {

		$this->container->get('Database')->query("SELECT id, email, password FROM {$this->_users} WHERE email = :email");
		$this->container->get('Database')->bind(':email', $email);
		$user = $this->container->get('Database')->single();

		return ($user['password'] === $password) ? true : false;

	}

	public function getUserDetails($email) {

		$this->container->get('Database')->query("SELECT id, email FROM {$this->_users} WHERE email = :email");
		$this->container->get('Database')->bind(':email', $email);
		$user = $this->container->get('Database')->single();

		if ($user && isset($user['id'])) {
			$user['user_id'] = $user['id'];
			unset($user['id']);
		}

		return ($user) ? $user : false;

	}

	/**
	 * Access Token Interface
	 */
	public function getAccessToken($access_token) {

		$this->container->get('OAuth2Database')->query("SELECT * FROM {$this->_access_tokens} WHERE access_token = :access_token");
		$this->container->get('OAuth2Database')->bind(':access_token', $access_token);
		$access_token = $this->container->get('OAuth2Database')->single();

		if ($access_token) {
			$access_token['expires'] = strtotime($access_token['expires']);
		}

		return $access_token;

	}

	public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null) {

		$expires = date('Y-m-d H:i:s', $expires);

		if ($this->getAccessToken($access_token)) {
			$this->container->get('OAuth2Database')->query("UPDATE {$this->_access_tokens} SET client_id = :client_id, expires = :expires, user_id = :user_id, scope = :scope WHERE access_token = :access_token");

		} else {
			$this->container->get('OAuth2Database')->query("INSERT INTO {$this->_access_tokens} (access_token, client_id, expires, user_id, scope) VALUES (:access_token, :client_id, :expires, :user_id, :scope)");

		}

		$this->container->get('OAuth2Database')->bindArray([
			':access_token' => $access_token,
			':client_id' => $client_id,
			':expires' => $expires,
			':user_id' => $user_id,
			':scope' => $scope
		]);

		return $this->container->get('OAuth2Database')->execute();

	}

	/**
	 * Refresh Token Interface
	 */
	public function getRefreshToken($refresh_token) {

		$this->container->get('OAuth2Database')->query("SELECT * FROM {$this->_refresh_tokens} WHERE refresh_token = :refresh_token");
		$this->container->get('OAuth2Database')->bind(':refresh_token', $refresh_token);
		$refresh_token = $this->container->get('OAuth2Database')->single();

		if ($refresh_token) {
			$refresh_token['expires'] = strtotime($refresh_token['expires']);
		}

		return $refresh_token;

	}

	public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null) {

		$expires = date('Y-m-d H:i:s', $expires);

		$this->container->get('OAuth2Database')->query("INSERT INTO {$this->_refresh_tokens} (refresh_token, client_id, user_id, expires, scope) VALUES (:refresh_token, :client_id, :user_id, :expires, :scope)");
		$this->container->get('OAuth2Database')->bindArray([
			':refresh_token' => $refresh_token,
			':client_id' => $client_id,
			':user_id' => $user_id,
			':expires' => $expires,
			':scope' => $scope
		]);
		return $this->container->get('OAuth2Database')->execute();

	}

	public function unsetRefreshToken($refresh_token) {

		$this->container->get('OAuth2Database')->query("DELETE FROM {$this->_refresh_tokens} WHERE refresh_token = :refresh_token");
		$this->container->get('OAuth2Database')->bind(':refresh_token', $refresh_token);
		$this->container->get('OAuth2Database')->execute();

	}
	

}