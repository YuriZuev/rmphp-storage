<?php

namespace Rmphp\Storage\Session;


class Session implements SessionInterface {

	const INT = "INT";
	const STRING = "STRING";

	public function __construct(string $name = "usi") {
		if(session_status() == PHP_SESSION_NONE) {
			if(in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)){
				session_id("cli");
			} elseif(!empty($name)) {
				session_name($name);
			}
			session_start();
		}
	}

	/** @inheritDoc */
	public function has(string $name = "") : bool {
		return (!empty($name)) ? isset($this->getSession()[$name]) : !empty($this->getSession());
	}

	/** @inheritDoc */
	public function get(string $name = "", string $type = ""): mixed {
		$session = $this->getSession();
		if(!empty($name = strtolower($name))) {
			if (!isset($session[$name])) return null;
			elseif ($type == self::STRING) {
				return (!empty($session[$name])) ? (string)$session[$name] : null;
			}
			elseif ($type == self::INT) {
				return (!empty((int)$session[$name]) || $session[$name]==0) ? (int)$session[$name] : null;
			}
			return $session[$name];
		}
		return $session;
	}

	/** @inheritDoc */
	public function set(string $name, $value = null) : void {
		$_SESSION[$name] = $value;
	}

	/** @inheritDoc */
	public function clear(string $name = null) : void {
		if (isset($name)) unset($_SESSION[$name]);
		else $_SESSION = [];
	}

	/** @inheritDoc */
	private function getSession() : array {
		return $_SESSION;
	}
}