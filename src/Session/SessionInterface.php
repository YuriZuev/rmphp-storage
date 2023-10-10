<?php
/**
 * Created by PhpStorm.
 * User: Zuev Yuri
 * Date: 10.10.2023
 * Time: 14:34
 */

namespace Rmphp\Storage\Session;

interface SessionInterface {

	/**
	 * @param string $name
	 * @return bool
	 */
	public function has(string $name = "") : bool;

	/**
	 * @param string $name
	 * @param string $type
	 * @return mixed
	 */
	public function get(string $name = "", string $type = ""): mixed;

	/**
	 * @param string $name
	 * @param $value
	 */
	public function set(string $name, $value = null) : void;

	/**
	 * @param string|null $name
	 * @return void
	 */
	public function clear(string $name = null) : void;

}