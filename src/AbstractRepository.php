<?php

namespace Rmphp\Storage;

use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Rmphp\Storage\Entity\ValueObjectInterface;

abstract class AbstractRepository implements RepositoryInterface {

	static  array $classes = [];

	/** @inheritDoc */
	public function createFromData(string $class, $data) : mixed {
		try {
			if(!isset(static::$classes[$class])) static::$classes[$class] = new ReflectionClass($class);
			$object = new $class;
			/** @var ReflectionProperty $property */
			foreach (static::$classes[$class]->getProperties() as $property) {
				// data[propertyName] ?? data[property_name] ?? null
				$value = $data[$property->getName()] ?? $data[strtolower(preg_replace("'([A-Z])'", "_$1", $property->getName()))] ?? null;
				// если есть внутренний метод (приоритетная обработка)
				if(static::$classes[$class]->hasMethod('set'.ucfirst($property->getName()))) $object->{'set'.ucfirst($property->getName())}($value);
				// Если тип свойства класс (valueObject)
				elseif($property->hasType() && class_exists($property->getType()->getName())) $object->{$property->getName()} = (is_object($value)) ? $value : new ($property->getType()->getName())($value);
				// если значения не пустое
				elseif(isset($value)) $object->{$property->getName()} = $value;
				// если значения может быть пустое
				elseif($property->getType()->allowsNull()) $object->{$property->getName()} = null;
			}
			return $object;
		}
		catch (ReflectionException $exception) {
			throw new RepositoryException($exception->getMessage());
		}
	}


	/** @inheritDoc */
	public function getAllProperties(object $class, callable $method = null) : array {
		$properties = $this->initProperties($class);
		return (isset($method)) ? array_map($method, $properties) : $properties;
	}


	/** @inheritDoc */
	public function getProperties(object $class, callable $method = null) : array {
		$properties = $this->initProperties($class);
		foreach ($properties as $fieldName => $value) {
			if(isset($value)) $out[$fieldName] = $value;
		}
		return (isset($method)) ? array_map($method, $out ?? []) : $out ?? [];
	}


	/**
	 * @param object $class
	 * @return array
	 */
	private function initProperties(object $class): array {
		$objectData = get_object_vars($class);
		foreach ($objectData as $fieldName => $value)
		{
			// если есть внутренний метод (приоритетная обработка)
			if(method_exists($class, 'get'.ucfirst($fieldName))) {
				$fieldValue[$fieldName] = $class->{'get'.ucfirst($fieldName)}($value);
			}
			// если тип свойства класс (valueObject)
			elseif($value instanceof ValueObjectInterface) {
				$fieldValue[$fieldName] = $value->get();
			}
			// если это логическое значение
			elseif(is_bool($value)){
				$fieldValue[$fieldName] = (int) $value;
			}
			// если это дробное число
			elseif(is_float($value)) {
				$fieldValue[$fieldName] = $value;
			}
			// если это целое число
			elseif(is_int($value)) {
				$fieldValue[$fieldName] = $value;
			}
			// если это строка
			elseif(is_string($value)) {
				$fieldValue[$fieldName] = $value;
			}
			// to option_id
			$fieldNameSnakeCase = strtolower(preg_replace("'([A-Z])'", "_$1", $fieldName));
			$out[$fieldNameSnakeCase] = $fieldValue[$fieldName] ?? null;
		}
		return $out ?? [];
	}

}
