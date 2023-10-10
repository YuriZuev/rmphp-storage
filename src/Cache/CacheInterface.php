<?php
/**
 * Created by PhpStorm.
 * User: Zuev Yuri
 */


namespace Rmphp\Storage\Cache;

interface CacheInterface {

	/**
	 * @param string $name
	 * @param $value
	 * @param int $expires
	 * @return bool
	 */
	public function save(string $name, $value, int $expires = 0) : bool;

	/**
	 * @param string $key
	 * @return mixed|null
	 */
	public function getItem(string $key);

	/**
	 * @param string $key
	 * @return bool
	 */
	public function hasItem(string $key) : bool;

	/**
	 * @param string $name
	 * @param callable $function
	 * @param int $expires
	 * @return mixed
	 */
	public function get(string $name, callable $function, int $expires = 0): mixed;

}