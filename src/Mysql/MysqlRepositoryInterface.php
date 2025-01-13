<?php
/**
 * Created by PhpStorm.
 * User: Zuev Yuri
 * Date: 12.01.2025
 * Time: 21:44
 */

namespace Rmphp\Storage\Mysql;

use Rmphp\Storage\Entity\EntityInterface;
use Rmphp\Storage\RepositoryException;
use Rmphp\Storage\RepositoryInterface;

interface MysqlRepositoryInterface extends RepositoryInterface {

	/**
	 * @param string $class
	 * @param bool|MysqlResultData $result
	 * @param callable|null $function
	 * @return mixed
	 * @throws RepositoryException
	 */
	public function createFromResult(string $class, bool|MysqlResultData $result, callable $function = null): mixed;

	/**
	 * @param string $class
	 * @param bool|MysqlResultData $result
	 * @param callable|null $function
	 * @return array
	 * @throws RepositoryException
	 */
	public function createListFromResult(string $class, bool|MysqlResultData $result, callable $function = null): array;

	/**
	 * @throws RepositoryException
	 */
	public function getEntityById(int $id, $require = false);

	/**
	 * @param EntityInterface $object
	 * @param string|null $table
	 * @return mixed
	 * @throws RepositoryException
	 */
	public function saveEntity(EntityInterface $object, string $table = null) : mixed;

	/**
	 * @param array $objects
	 * @param string|null $table
	 * @return array
	 * @throws RepositoryException
	 */
	public function saveGroup(array $objects, string $table = null): array;

	/**
	 * @param EntityInterface $object
	 * @param string|null $table
	 * @return bool
	 * @throws RepositoryException
	 */
	public function deleteEntity(EntityInterface $object, string $table = null) : bool;

	/**
	 * @return array
	 */
	public function getStorageLogs() : array;


}
