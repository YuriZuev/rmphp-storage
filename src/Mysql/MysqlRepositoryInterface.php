<?php
/**
 * Created by PhpStorm.
 * User: Zuev Yuri
 * Date: 12.01.2025
 * Time: 21:44
 */

namespace Rmphp\Storage\Mysql;

use Rmphp\Storage\Entity\EntityInterface;
use Rmphp\Storage\Exception\RepositoryException;
use Rmphp\Storage\Repository\RepositoryInterface;

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
	 * @param int $id
	 * @param string|null $table
	 * @return mixed
	 * @throws RepositoryException
	 */
	public function getEntityById(int $id, string $table = null): mixed;

	/**
	 * @param EntityInterface $object
	 * @param string|null $table
	 * @return mixed
	 * @throws RepositoryException
	 */
	public function saveEntity(EntityInterface $object, string $table = null) : mixed;

	/**
	 * @param array $data
	 * @param string|null $table
	 * @param string $primaryKey
	 * @return mixed
	 * @throws RepositoryException
	 */
	public function saveData(array $data, string $table = null, string $primaryKey = 'id') : mixed;

	/**
	 * @param array $objects
	 * @param string|null $table
	 * @return array
	 * @throws RepositoryException
	 */
	public function saveEntityGroup(array $objects, string $table = null): array;

	/**
	 * @param array $objects
	 * @param string|null $table
	 * @param string $primaryKey
	 * @return array
	 * @throws RepositoryException
	 */
	public function saveDataGroup(array $objects, string $table = null, string $primaryKey = 'id'): array;

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

	/**
	 * @param string $table
	 * @return void
	 */
	public function setTable(string $table) : void;

	/**
	 * @param string $entity
	 * @return void
	 */
	public function setEntity(string $entity) : void;

	/**
	 * @param bool $debug
	 * @return void
	 */
	public function setDebug(bool $debug) : void;

}
