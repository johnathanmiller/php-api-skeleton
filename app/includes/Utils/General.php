<?php

namespace App\Utils;

class General {

	public static function isEmailValid($str) {
		return (filter_var($str, FILTER_VALIDATE_EMAIL)) ? true : false;
	}

	public static function sanitizeEmail($str) {
		return filter_var($str, FILTER_SANITIZE_EMAIL);
	}

	public static function hashPassword($str) {
		return password_hash($str, PASSWORD_BCRYPT);
	}

	public static function currentDate() {
		return date('Y-m-d H:i:s', time());
	}

}