<?php

namespace Rmphp\Storage;

use Rmphp\ODM\ObjectDataMapper;
use Rmphp\ODM\ODMException;
use Rmphp\Storage\Mysql\MysqlRepositoryInterface;
use Rmphp\Storage\Mysql\MysqlResultData;
use Rmphp\Storage\Mysql\MysqlStorageInterface;
use Rmphp\Storage\Repository\EntityInterface;
use Rmphp\Storage\Repository\RepositoryException;
use Throwable;

abstract class AbstractMysqlRepository implements MysqlRepositoryInterface {

	public const DEBUG = false;
	public const TABLE = null;
	public const ENTITY = null;

	private string $table;
	private string $entity;
	private bool $debug;

	public function __construct(
		public readonly MysqlStorageInterface $mysql,
		protected readonly ObjectDataMapper $mapper
	) {}

	/**
	 * @inheritDoc
	 */
	final public function getData(object $object, callable $method = null) : array {
		try {
			return $this->mapper->getDataFromObject($object, $method);
		} catch(ODMException $odmException) {
			throw new RepositoryException($odmException->getMessage(), $odmException->getCode());
		}
	}

	/**
	 * @inheritDoc
	 */
	final public function createFromData(string $class, array|object $data, bool $withEmpty = true) : object {
		try{
			return $this->mapper->createObjectFromData($class, $data, $withEmpty);
		} catch(ODMException $odmException) {
			throw new RepositoryException($odmException->getMessage(), $odmException->getCode());
		}
	}

	/**
	 * @inheritDoc
	 */
	final public function updateFromData(object $object, array|object $data, bool $withEmpty = true) : object {
		try{
			return $this->mapper->updateObjectFromData($object, $data, $withEmpty);
		} catch(ODMException $odmException) {
			throw new RepositoryException($odmException->getMessage(), $odmException->getCode());
		}
	}

	/**
	 * @inheritDoc
	 */
	final public function createFromResult(string $class, ?MysqlResultData $result, callable $function = null): mixed {
		try {
			if($result instanceof MysqlResultData) {
				$val = (isset($function)) ? $function($result->fetchOne()) : $result->fetchOne();
				$out = $this->mapper->createObjectFromData($class, $val);
			}
			return $out ?? null;
		} catch(ODMException $odmException) {
			throw new RepositoryException($odmException->getMessage(), $odmException->getCode());
		}
	}

	/**
	 * @inheritDoc
	 */
	final public function createListFromResult(string $class, ?MysqlResultData $result, callable $function = null): array {
		try {
			if($result instanceof MysqlResultData) {
				foreach($result->fetch() as $resultValue) {
					$val = (isset($function)) ? $function($resultValue) : $resultValue;
					$out[] = $this->mapper->createObjectFromData($class, $val);
				}
			}
			return $out ?? [];
		} catch(ODMException $odmException) {
			throw new RepositoryException($odmException->getMessage(), $odmException->getCode());
		}
	}

	/**
	 * @inheritDoc
	 */
	final public function getEntityById(int $id, string $table = null): mixed {
		try {
			if(!isset($table)) $table = $this->getTable();
			if($result = $this->mysql->findById($table, $id)) $out = $this->mapper->createObjectFromData($this->getEntityClass(), $result);
			return $out ?? null;
		} catch(ODMException $odmException) {
			throw new RepositoryException($odmException->getMessage(), $odmException->getCode());
		}
	}

	/**
	 * @inheritDoc
	 */
	final public function saveEntity(EntityInterface $object, string $table = null) : mixed {
		try {
			if(!isset($table)) $table = $this->getTable();
			$in = $this->mapper->getDataFromObject($object, function ($value){
				return (is_string($value)) ? $this->mysql->escapeStr($value) : $value;
			});
			if($this->isDebug()) {$this->debug($object, $in, $table, ...$this->getDebugExtraData()); exit;}

			if (!empty($object->getId()) && !empty($this->mysql->findById($table, $object->getId()))) {
				$this->mysql->updateById($table, $in, $object->getId());
				return $object->getId();
			} else {
				$this->mysql->insert($table, $in);
				return (is_string($object->getId())) ? $object->getId() : $this->mysql->mysql()->insert_id;
			}
		}
		catch (ODMException|Throwable $throwable) {
			throw new RepositoryException($throwable->getMessage());
		}
	}

	/** @inheritDoc */
	final public function saveData(array $data, string $table = null, string $primaryKey = 'id') : mixed {
		if(!isset($table)) $table = $this->getTable();
		$in = array_map(function ($value){
			return (is_string($value)) ? $this->mysql->escapeStr($value) : $value;
		}, $data);
		if($this->isDebug()) {$this->debug($data, $in, $table, ...$this->getDebugExtraData()); exit;}
		try {
			if (!empty($data[$primaryKey]) && !empty($this->mysql->findById($table, $data[$primaryKey], $primaryKey))) {
				$this->mysql->updateById($table, $in, $data[$primaryKey]);
				return $data[$primaryKey];
			} else {
				$this->mysql->insert($table, $in);
				return (is_string($data[$primaryKey])) ? $data[$primaryKey] : $this->mysql->mysql()->insert_id;
			}
		} catch (Throwable $throwable) {throw new RepositoryException($throwable->getMessage());}
	}

	/** @inheritDoc */
	final public function saveEntityGroup(array $objects, string $table = null): array {
		try{
			$this->mysql->mysql()->begin_transaction();
			foreach($objects as $object) $id[] = $this->saveEntity($object, $table);
			$this->mysql->mysql()->commit();
			return $id ?? [];
		}
		catch (\Exception $exception){
			$this->mysql->mysql()->rollback();
			throw new RepositoryException($exception->getMessage());
		}
	}

	/** @inheritDoc */
	final public function saveDataGroup(array $objects, string $table = null, string $primaryKey = 'id'): array {
		try{
			$this->mysql->mysql()->begin_transaction();
			foreach($objects as $object) $id[] = $this->saveData($object, $table, $primaryKey);
			$this->mysql->mysql()->commit();
			return $id ?? [];
		}
		catch (\Exception $exception){
			$this->mysql->mysql()->rollback();
			throw new RepositoryException($exception->getMessage());
		}
	}

	/** @inheritDoc */
	final public function deleteEntity(EntityInterface $object, string $table = null) : bool {
		if(!isset($table)) $table = $this->getTable();
		if(!empty($object->getId())){
			return $this->mysql->deleteById($table, $object->getId());
		}
		return false;
	}

	/** @inheritDoc */
	final public function getStorageLogs() : array {
		return $this->mysql->getLogs();
	}

	/** @inheritDoc */
	final public function setTable(string $table) : void {
		$this->table = $table;
	}

	/** @inheritDoc */
	final public function setEntity(string $entity) : void {
		$this->entity = $entity;
	}

	/** @inheritDoc */
	final public function setDebug(bool $debug) : void {
		$this->debug = $debug;
	}


	/**
	 * @return string
	 * @throws RepositoryException
	 */
	private function getTable() : string {
		if(!empty($this->table)) return $this->table;
		if(!empty(static::TABLE)) return static::TABLE;
		throw new RepositoryException("Имя таблицы не задано");
	}

	/**
	 * @return string
	 * @throws RepositoryException
	 */
	private function getEntityClass() : string {
		if(!empty($this->entity)) return $this->entity;
		if(!empty(static::ENTITY)) return static::ENTITY;
		throw new RepositoryException("Не указан объект");
	}

	/**
	 * @return bool
	 */
	private function isDebug(): bool {
		if(!empty($this->debug)) return $this->debug;
		if(!empty(static::DEBUG)) return static::DEBUG;
		return false;
	}

	/**
	 * @return array
	 */
	protected function getDebugExtraData() : array {
		return [$this->mapper->getRepositoryStack(), $this->mapper->getClassesCache(), $this->mapper->getAttributesObjectsCache()];
	}

	/**
	 * @param ...$arg
	 * @return void
	 */
	protected function debug(...$arg) : void {
		if(function_exists('dd')) dd(...$arg);
		if(function_exists('vdd')) vdd(...$arg);
		var_dump(...$arg);
	}

}
