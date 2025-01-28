<?php

namespace Rmphp\Storage;

use Rmphp\Storage\Entity\EntityInterface;
use Rmphp\Storage\Mysql\MysqlRepositoryInterface;
use Rmphp\Storage\Mysql\MysqlResultData;
use Rmphp\Storage\Mysql\MysqlStorageInterface;

abstract class AbstractMysqlRepository extends AbstractRepository implements MysqlRepositoryInterface {

	public const DEBUG = false;
	public const TABLE = null;
	public const ENTITY = null;

	private string $table;
	private string $entity;
	private bool $debug;

	public function __construct(
		public readonly MysqlStorageInterface $mysql
	) {}


	/** @inheritDoc */
	public function createFromResult(string $class, bool|MysqlResultData $result, callable $function = null): mixed {
		if($result instanceof MysqlResultData) {
			$val = (isset($function)) ? $function($result->fetchOne()) : $result->fetchOne();
			$out = $this->createFromData($class, $val);
		}
		return $out ?? null;
	}


	/** @inheritDoc */
	public function createListFromResult(string $class, bool|MysqlResultData $result, callable $function = null): array {
		if($result instanceof MysqlResultData) {
			foreach($result->fetch() as $resultValue) {
				$val = (isset($function)) ? $function($resultValue) : $resultValue;
				$out[] = $this->createFromData($class, $val);
			}
		}
		return $out ?? [];
	}


	/** @inheritDoc */
	public function getEntityById(int $id, string $table = null): mixed {
		if(!isset($table)) $table = $this->getTable();
		if($result = $this->mysql->findById($table, $id)) $out = $this->createFromData($this->getEntityClass(), $result);
		return $out ?? null;
	}


	/** @inheritDoc */
	public function saveEntity(EntityInterface $object, string $table = null) : mixed {
		if(!isset($table)) $table = $this->getTable();
		$in = $this->getProperties($object, function ($value){
			return (is_string($value)) ? $this->mysql->escapeStr($value) : $value;
		});
		if($this->getDebug()) {$this->debug($object, $in, $table); exit;}
		try {
			if (!empty($object->getId()) && !empty($this->mysql->findById($table, $object->getId()))) {
				$this->mysql->updateById($table, $in, $object->getId());
				return $object->getId();
			} else {
				$this->mysql->insert($table, $in);
				return (is_string($object->getId())) ? $object->getId() : $this->mysql->mysql()->insert_id;
			}
		} catch (\Throwable $throwable) {throw new RepositoryException($throwable->getMessage());}
	}


	/** @inheritDoc */
	public function saveData(array $data, string $table = null, string $primaryKey = 'id') : mixed {
		if(!isset($table)) $table = $this->getTable();
		$in = array_map(function ($value){
			return (is_string($value)) ? $this->mysql->escapeStr($value) : $value;
		}, $data);
		if($this->getDebug()) {$this->debug($data, $in, $table); exit;}
		try {
			if (!empty($data[$primaryKey]) && !empty($this->mysql->findById($table, $data[$primaryKey], $primaryKey))) {
				$this->mysql->updateById($table, $in, $data[$primaryKey]);
				return $data[$primaryKey];
			} else {
				$this->mysql->insert($table, $in);
				return (is_string($data[$primaryKey])) ? $data[$primaryKey] : $this->mysql->mysql()->insert_id;
			}
		} catch (\Throwable $throwable) {throw new RepositoryException($throwable->getMessage());}
	}


	/** @inheritDoc */
	public function saveEntityGroup(array $objects, string $table = null): array {
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
	public function saveDataGroup(array $objects, string $table = null, string $primaryKey = 'id'): array {
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
	public function deleteEntity(EntityInterface $object, string $table = null) : bool {
		if(!isset($table)) $table = $this->getTable();
		if(!empty($object->getId())){
			return $this->mysql->deleteById($table, $object->getId());
		}
		return false;
	}


	/** @inheritDoc */
	public function getStorageLogs() : array {
		return $this->mysql->getLogs();
	}


	/** @inheritDoc */
	public function setTable(string $table) : void {
		$this->table = $table;
	}


	/** @inheritDoc */
	public function setEntity(string $entity) : void {
		$this->entity = $entity;
	}


	/** @inheritDoc */
	public function setDebug(bool $debug) : void {
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
	private function getDebug(): bool {
		if(!empty($this->debug)) return $this->debug;
		if(!empty(static::DEBUG)) return static::DEBUG;
		return false;
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
