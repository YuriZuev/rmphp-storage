<?php

namespace Rmphp\Storage;

use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Rmphp\Storage\Entity\ValueObjectInterface;

abstract class AbstractRepository implements RepositoryInterface {

	static  array $classes = [];

	/** @inheritDoc */
	public function createFromData(string $class, $data) : object {
		try {
			if(!isset(static::$classes[$class])) static::$classes[$class] = new ReflectionClass($class);
			return $this->fillObject(static::$classes[$class], new $class, $data);
		}
		catch (ReflectionException $exception) {
			throw new RepositoryException($exception->getMessage());
		}
	}


	/** @inheritDoc */
	public function updateFromData(object $object, array $data) : object {
		try {
			$class = get_class($object);
			if(!isset(static::$classes[$class])) static::$classes[$class] = new ReflectionClass($class);
			return $this->fillObject(static::$classes[$class], clone $object, $data, true);
		}
		catch (RepositoryException|ReflectionException $exception) {
			throw new RepositoryException($exception->getMessage());
		}
	}


	/** @inheritDoc */
	public function getProperties(object $object, callable $method = null) : array {
		try{
			$class = get_class($object);
			if(!isset(static::$classes[$class])) static::$classes[$class] = new ReflectionClass($class);

			/** @var ReflectionProperty $property */
			foreach(static::$classes[$class]->getProperties() as $property){
				if(!$property->isInitialized($object) || is_array($property->getValue($object))) continue;

				if(static::$classes[$class]->hasMethod('get'.ucfirst($property->getName()))){
					$fieldValue[$property->getName()] = $object->{'get'.ucfirst($property->getName())}($property->getValue($object));
				}
				elseif($property->hasType() && class_exists($property->getType()->getName()) && $property->getValue($object) instanceof ValueObjectInterface){
					$fieldValue[$property->getName()] = $property->getValue($object)->getValue();
				}
				elseif(is_bool($property->getValue($object))){
					$fieldValue[$property->getName()] = (int) $property->getValue($object);
				}
				else {
					$fieldValue[$property->getName()] = $property->getValue($object);
				}

				if(false !== $fieldValue[$property->getName()]) {
					$out[strtolower(preg_replace("'([A-Z])'", "_$1", $property->getName()))] = $fieldValue[$property->getName()];
				}
			}
			return (isset($method)) ? array_map($method, $out ?? []) : $out ?? [];
		}
		catch (ReflectionException $exception) {
			throw new RepositoryException($exception->getMessage());
		}
	}


	/**
	 * @param ReflectionClass $class
	 * @param object $object
	 * @param array $data
	 * @param bool $update
	 * @return mixed
	 * @throws RepositoryException
	 */
	private function fillObject(ReflectionClass $class, object $object, array $data, bool $update = false) : mixed {
		try {
			foreach($class->getProperties() as $property){
				if($update && !array_key_exists($property->getName(), $data) && !array_key_exists(strtolower(preg_replace("'([A-Z])'", "_$1", $property->getName())), $data)) continue;

				// data[propertyName] ?? data[property_name] ?? null
				$value = $data[$property->getName()] ?? $data[strtolower(preg_replace("'([A-Z])'", "_$1", $property->getName()))] ?? null;

				// если есть внутренний метод (приоритетная обработка)
				if($class->hasMethod('set'.ucfirst($property->getName()))) {
					$object->{'set'.ucfirst($property->getName())}($value);
				}
				// Если тип свойства класс (valueObject)
				elseif($property->hasType() && class_exists($property->getType()->getName())) {
					$object->{$property->getName()} = (is_object($value)) ? $value : new ($property->getType()->getName())($value);
				}
				// если значения не пустое
				elseif(isset($value)) {
					$object->{$property->getName()} = $value;
				}
				// если значения может быть пустое
				elseif($property->getType()->allowsNull()) {
					$object->{$property->getName()} = null;
				}
			}
			return $object;
		}
		catch (ReflectionException $exception) {
			throw new RepositoryException($exception->getMessage());
		}
	}
}
