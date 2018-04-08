<?php

namespace App\Controllers;

use App\Utils\General;

class User {

	private $_users = 'api_users';

	public function __construct($container) {
		$this->container = $container;
	}

	// CREATE USER
	public function createUser($request, $response, $args) {

		$post_body = $request->getParsedBody();

		if (!empty($post_body)) {

			if (isset($post_body['email']) && isset($post_body['password'])) {

				if (General::isEmailValid($post_body['email'])) {

					$email = General::sanitizeEmail($post_body['email']);
					$password = General::hashPassword($post_body['password']);
					$date = General::currentDate();

					$this->container->get('Database')->query("SELECT id FROM {$this->_users} WHERE email = :email");
					$this->container->get('Database')->bind(':email', $email);
					$result = $this->container->get('Database')->single();

					if (!$result) {

						try {

							$this->container->get('Database')->query("INSERT INTO {$this->_users} (email, password, joined_at) VALUES (:email, :password, :joined_at)");
							$this->container->get('Database')->bindArray([
								':email' => $email,
								':password' => $password,
								':joined_at' => $date
							]);
							$this->container->get('Database')->execute();

							$new_response = $response->withJson([
								'data' => [
									'message' => 'User created'
								]
							], 201);

						} catch (Exception $e) {
							$new_response = $response->withJson([
								'error' => [
									'message' => $e->getMessage()
								]
							], 400);
						}

					} else {
						$new_response = $response->withJson([
							'error' => [
								'message' => 'User already exists'
							]
						], 409);
					}

				} else {
					$new_response = $response->withJson([
						'error' => [
							'message' => 'Email is invalid'
						]
					], 400);
				}

			} else {
				$new_response = $response->withJson([
					'error' => [
						'message' => 'Post body is missing data'
					]
				], 400);
			}

		} else {
			$new_response = $response->withJson([
				'error' => [
					'message' => 'Post body is empty'
				]
			], 400);
		}

		return $new_response;

	}

	// RETRIEVE USER
	public function getUser($request, $response, $args) {

		if (is_numeric($args['id'])) {

			$cache_key = 'user_'. $args['id'];
			$user_cache = $this->container->get('Redis')->hgetall($cache_key);

			if ($user_cache) {

				$user = $user_cache;

				if (!isset($user['id']) || !isset($user['email'])) {

					$this->container->get('Database')->query("SELECT id, email, joined_at, updated_at FROM {$this->_users} WHERE id = :id");
					$this->container->get('Database')->bind(':id', $args['id']);
					$user = $this->container->get('Database')->single();

					$this->cacheUser($cache_key, $user);

					$user = $this->container->get('Redis')->hgetall($cache_key);

				}

			} else {

				$this->container->get('Database')->query("SELECT id, email, joined_at, updated_at FROM {$this->_users} WHERE id = :id");
				$this->container->get('Database')->bind(':id', $args['id']);
				$user = $this->container->get('Database')->single();

				$this->cacheUser($cache_key, $user);

			}

			if ($user) {

				foreach ($user as $k => $v) {
					if ($v === null) {
						unset($user[$k]);
					}
				}

				$new_response = $response->withJson([
					'user' => $user
				], 200);

			} else {
				$new_response = $response->withJson([
					'error' => [
						'message' => 'User not found'
					]
				], 404);
			}

		} else {
			$new_response = $response->withJson([
				'error' => [
					'message' => 'Invalid request'
				]
			], 400);
		}

		return $new_response;

	}

	// UPDATE USER
	public function updateUser($request, $response, $args) {

		$post_body = $request->getParsedBody();

		if (is_numeric($args['id'])) {

			if (!empty($post_body)) {

				$cache_key = 'user_'. $args['id'];
				$user_cache = $this->container->get('Redis')->hgetall($cache_key);

				if ($user_cache) {

					$user = $user_cache;

					if (!isset($user['id']) || !isset($user['email'])) {

						$this->container->get('Database')->query("SELECT id, email, joined_at, updated_at FROM {$this->_users} WHERE id = :id");
						$this->container->get('Database')->bind(':id', $args['id']);
						$user = $this->container->get('Database')->single();

						$this->cacheUser($cache_key, $user);

						$user = $this->container->get('Redis')->hgetall($cache_key);

					}

				} else {

					$this->container->get('Database')->query("SELECT id, email, joined_at, updated_at FROM {$this->_users} WHERE id = :id");
					$this->container->get('Database')->bind(':id', $args['id']);
					$user = $this->container->get('Database')->single();

					$this->cacheUser($cache_key, $user);

				}

				if ($user) {

					$set_keys = [];
					$set_values = [];

					$set_values += [':id' => $args['id']];

					foreach ($post_body as $k => $v) {

						if (in_array($k, ['email', 'password'])) {

							if ($k === 'password') {
								$v = General::hashPassword($v);

								$set_keys[] = $k .' = :'. $k;
								$set_values += [':'. $k => $v];

							} else {

								if ($user_cache) {

									if ($this->container->get('Redis')->hget($cache_key, $k) !== $v) {

										$this->container->get('Redis')->hset($cache_key, $k, $v);

										$set_keys[] = $k .' = :'. $k;
										$set_values += [':'. $k => $v];
									}

								} else {
									$set_keys[] = $k .' = :'. $k;
									$set_values += [':'. $k => $v];
								}

							}

						}

					}

					if (!empty($set_keys)) {

						$updated_at = General::currentDate();
						$set_keys[] = 'updated_at = :updated_at';
						$set_values += [':updated_at' => $updated_at];

						$set_keys = implode(', ', $set_keys);

						try {

							$this->container->get('Database')->query("UPDATE {$this->_users} SET {$set_keys} WHERE id = :id");
							$this->container->get('Database')->bindArray($set_values);
							$this->container->get('Database')->execute();

							$new_response = $response->withJson([
								'data' => [
									'message' => 'User updated'
								]
							], 200);

						} catch (Exception $e) {
							$new_response = $response->withJson([
								'error' => [
									'message' => $e->getMessage()
								]
							], 400);
						}

					} else {
						$new_response = $response->withJson([
							'error' => [
								'message' => 'No data modified'
							]
						], 304);
					}

				} else {
					$new_response = $response->withJson([
						'error' => [
							'message' => 'User not found'
						]
					], 404);
				}

			} else {
				$new_response = $response->withJson([
					'error' => [
						'message' => 'Post body is empty'
					]
				], 400);
			}

		} else {
			$new_response = $response->withJson([
				'error' => [
					'message' => 'Invalid request'
				]
			], 400);
		}

		return $new_response;

	}

	// DELETE USER
	public function deleteUser($request, $response, $args) {

		if (is_numeric($args['id'])) {

			$this->container->get('Database')->query("SELECT id, email, joined_at, updated_at FROM {$this->_users} WHERE id = :id");
			$this->container->get('Database')->bind(':id', $args['id']);
			$user = $this->container->get('Database')->single();

			if ($user) {

				$this->container->get('Database')->query("DELETE FROM {$this->_users} WHERE id = :id");
				$this->container->get('Database')->bind(':id', $args['id']);
				$this->container->get('Database')->execute();

				$new_response = $response->withJson([
					'data' => [
						'message' => 'User deleted'
					]
				], 200);

			} else {
				$new_response = $response->withJson([
					'error' => [
						'message' => 'User not found'
					]
				], 404);
			}

		} else {
			$new_response = $response->withJson([
				'error' => [
					'message' => 'Invalid request'
				]
			], 400);
		}

		return $new_response;

	}

	// CACHE USER
	private function cacheUser($key, $data) {

		foreach ($data as $k => $v) {
			if (!empty($v)) {
				$this->container->get('Redis')->hset($key, $k, $v);
			}
		}

		$this->container->get('Redis')->hset($key, 'cached_at', General::currentDate());
		$this->container->get('Redis')->expire($key, 3600 * 24);

	}

}