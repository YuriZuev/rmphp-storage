<?php
/**
 * Created by PhpStorm.
 * User: Zuev Yuri
 * Date: 12.01.2025
 * Time: 21:48
 */

namespace Rmphp\Storage\Repository;


interface RepositoryInterface {

	/**
	 * @param object $object
	 * @param callable|null $method
	 * @return array
	 * @throws RepositoryException
	 */
	public function getProperties(object $object, callable $method = null) : array;


	/**
	 * @param string $class
	 * @param array|object $data
	 * @return mixed
	 * @throws RepositoryException
	 */
	public function createFromData(string $class, array|object $data) : mixed;


	/**
	 * @param object $object
	 * @param array|object $data
	 * @return mixed
	 * @throws RepositoryException
	 */
	public function updateFromData(object $object, array|object $data) : mixed;


	/**
	 * @return array
	 */
	public function getRepositoryStack() : array;

}
