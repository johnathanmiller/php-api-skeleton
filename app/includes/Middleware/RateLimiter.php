<?php

namespace App\Middleware;

class RateLimiter {

	protected $ip;
	protected $cache_key;
	protected $remaining;
	protected $reset;

	public function __construct($container) {
		$this->container = $container;
		$this->limit = $this->container->get('settings')['rateLimit'];
	}

	public function __invoke($request, $response, $next) {

		$this->ip = $this->getIP();
		$this->cache_key = 'ip_'. $this->ip;

		if ($this->throttle() !== true) {

			$this->remaining = $this->limit['requests'] - $this->container->get('Redis')->zscore($this->cache_key, 'requests');

			if (!in_array($this->ip, $this->IPWhiteList())) {
				$new_response = $next($request, $response)->withHeader('X-RateLimit-Limit', $this->limit['requests'])->withHeader('X-RateLimit-Remaining', $this->remaining);

			} else {
				$new_response = $next($request, $response);
			}

		} else {
			$this->reset = $this->container->get('Redis')->zscore($this->cache_key, 'timestamp') + $this->limit['seconds'];

			$new_response = $response->withHeader('X-RateLimit-Limit', $this->limit['requests'])->withHeader('X-RateLimit-Reset', $this->reset)->withStatus(429);
		}

		return $new_response;

	}

	protected function throttle() {

		$cache_key = $this->cache_key;
		$cache = $this->container->get('Redis')->zrange($cache_key, 0, -1);

		if (!in_array($this->getIP(), $this->IPWhiteList())) {

			if (!$cache) {
				$this->resetCount($cache_key);

			} else {
				if ($this->container->get('Redis')->zscore($cache_key, 'timestamp') - time() < $this->limit['seconds']) {

					if ($this->container->get('Redis')->zscore($cache_key, 'requests') >= $this->limit['requests']) {
						return true;

					} else {
						$this->container->get('Redis')->zincrby($cache_key, 1, 'requests');
						$this->container->get('Redis')->zadd($cache_key, time(), 'timestamp');
						$this->container->get('Redis')->expire($cache_key, $this->limit['seconds']);
					}

				} else {
					$this->resetCount($cache_key);
				}
			}

		}

	}

	protected function resetCount($cache_key) {

		$this->container->get('Redis')->zadd($cache_key, 1, 'requests');
		$this->container->get('Redis')->zadd($cache_key, time(), 'timestamp');
		$this->container->get('Redis')->expire($cache_key, $this->limit['seconds']);

	}

	protected function IPWhiteList() {

		return [
			'127.0.0.1'
		];

	}

	protected function getIP() {

		return getenv('HTTP_CLIENT_IP') ?:
		getenv('HTTP_X_FORWARDED_FOR') ?:
		getenv('HTTP_X_FORWARDED') ?:
		getenv('HTTP_FORWARDED_FOR') ?:
		getenv('HTTP_FORWARDED') ?:
		getenv('REMOTE_ADDR');

	}

}