<?php

namespace Rmphp\Storage;

use Rmphp\Storage\Entity\EntityInterface;
use Rmphp\Storage\Mysql\MysqlRepositoryInterface;
use Rmphp\Storage\Mysql\MysqlResultData;
use Rmphp\Storage\Mysql\MysqlStorageInterface;

abstract class AbstractMysqlRepository extends AbstractRepository implements MysqlRepositoryInterface {

	public const DEBUG = false;
	public const TABLE = null;
	public const ENTINY = null;


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
	public function getEntityById(int $id, $require = false) {
		$this->checkConst();
		if($result = $this->mysql->findById(static::TABLE, $id)) $out = $this->createFromData(static::ENTINY, $result);
		if(empty($out) && $require) throw new RepositoryException("Запись не найдена");
		return $out ?? null;
	}


	/** @inheritDoc */
	public function saveEntity(EntityInterface $object) : mixed {
		$this->checkConst();
		$in = $this->getProperties($object, function ($value){
			return (is_string($value)) ? $this->mysql->escapeStr($value) : $value;
		});
		if(static::DEBUG) {$this->getSaveDebug($object, $in, static::TABLE); exit;}
		try {
			if (!empty($object->getId()) && !empty($this->mysql->findById(static::TABLE, $object->getId()))) {
				$this->mysql->updateById(static::TABLE, $in, $object->getId());
				return $object->getId();
			} else {
				$this->mysql->insert(static::TABLE, $in);
				return (is_string($object->getId())) ? $object->getId() : $this->mysql->mysql()->insert_id;
			}
		} catch (\Throwable $throwable) {throw new RepositoryException($throwable->getMessage());}
	}


	/** @inheritDoc */
	public function saveGroup(array $objects): array {
		try{
			$this->mysql->mysql()->begin_transaction();
			foreach($objects as $object) $id[] = $this->saveEntity($object);
			$this->mysql->mysql()->commit();
			return $id ?? [];
		}
		catch (\Exception $exception){
			$this->mysql->mysql()->rollback();
			throw new RepositoryException($exception->getMessage());
		}
	}


	/** @inheritDoc */
	public function deleteEntity(EntityInterface $object) : bool {
		$this->checkConst();
		if(!empty($object->getId())){
			return $this->mysql->deleteById(static::TABLE, $object->getId());
		}
		return false;
	}


	/** @inheritDoc */
	public function getStorageLogs() : array {
		return $this->mysql->getLogs();
	}


	/**
	 * @param ...$arg
	 * @return void
	 */
	protected function getSaveDebug(...$arg) : void {
		if(function_exists('dd')) dd(...$arg);
		if(function_exists('vdd')) vdd(...$arg);
		var_dump(...$arg);
	}


	/**
	 * @throws RepositoryException
	 */
	protected function checkConst(): void {
		if(empty(static::TABLE)) throw new RepositoryException("Имя таблицы не задано");
		if(empty(static::ENTINY)) throw new RepositoryException("Не указан объект");
	}

}
