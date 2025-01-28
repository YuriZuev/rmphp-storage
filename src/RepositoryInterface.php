<?php
/**
 * Created by PhpStorm.
 * User: Zuev Yuri
 * Date: 12.01.2025
 * Time: 21:48
 */

namespace Rmphp\Storage;

use ReflectionException;
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
	 * @param object $object
	 * @param array $data
	 * @return mixed
	 * @throws RepositoryException
	 */
	public function updateFromData(object $object, array $data) : mixed;


	/**
	 * @param object $object
	 * @param callable|null $method
	 * @return array
	 * @throws RepositoryException
	 */
	public function getProperties(object $object, callable $method = null) : array;

}
