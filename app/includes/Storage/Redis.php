<?php

namespace App\Storage;

class Redis {

	private $client;

	public function __construct($scheme, $host, $port) {

		\Predis\Autoloader::register();

		try {

			$this->client = new \Predis\Client([
				'scheme' => $scheme,
				'host' => $host,
				'port' => $port
			]);

		} catch (Exception $e) {
			echo $e->getMessage();
		}

	}

	public function hgetall($key) {
		return $this->client->hgetall($key);
	}

	public function hget($key, $field) {
		return $this->client->hget($key, $field);
	}

	public function hset($key, $field, $value) {
		$this->client->hset($key, $field, $value);
	}

	public function zadd($key, $score, $member) {
		$this->client->zadd($key, $score, $member);
	}

	public function zscore($key, $member) {
		return $this->client->zscore($key, $member);
	}

	public function zincrby($key, $increment, $member) {
		$this->client->zincrby($key, $increment, $member);
	}

	public function zrange($key, $start, $stop) {
		return $this->client->zrange($key, $start, $stop);
	}

	public function expire($key, $seconds) {
		$this->client->expire($key, $seconds);
	}

}