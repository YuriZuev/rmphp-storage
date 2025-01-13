<?php
/**
 * Created by PhpStorm.
 * User: Zuev Yuri
 * Date: 12.01.2025
 * Time: 21:48
 */

namespace Rmphp\Storage;

use Rmphp\Storage\Entity\EntityInterface;

interface RepositoryInterface {

	/**
	 * @param string $class
	 * @param $data
	 * @return object
	 * @throws RepositoryException
	 */
	public function createFromData(string $class, $data) : mixed;


	/**
	 * @param object $class
	 * @param callable|null $method
	 * @return array
	 */
	public function getAllProperties(object $class, callable $method = null) : array;


	/**
	 * @param object $class
	 * @param callable|null $method
	 * @return array
	 */
	public function getProperties(object $class, callable $method = null) : array;

}
