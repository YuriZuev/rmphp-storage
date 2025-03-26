<?php

namespace Rmphp\Storage\Component;

use Exception;
use ReflectionClass;
use ReflectionException;

class AbstractDataObject {

	private static array $constructorEmptyAvailableClasses = [];
	private static array $stack  = [];

	/**
	 * @param ReflectionClass $class
	 * @param object $object
	 * @param array $data
	 * @param bool $update
	 * @param bool $onlyNotNull
	 * @return mixed
	 * @throws Exception
	 */
	protected static function fillObject(ReflectionClass $class, object $object, array $data, bool $update = false, bool $onlyNotNull = false) : mixed {
		try {
			$value = [];
			foreach($class->getProperties() as $property){
				$prop[$property->getName()] = ($property->hasType()) ? $property->getType()->getName() : "";
				// значение в массиве по ключю с именем свойства
				if(array_key_exists($property->getName(), $data)){
					$value[$property->getName()] = $data[$property->getName()];
				}
				// значение в массиве по ключю с именем свойства в snake case
				elseif(array_key_exists(strtolower(preg_replace("'([A-Z])'", "_$1", $property->getName())), $data)){
					$value[$property->getName()] = $data[strtolower(preg_replace("'([A-Z])'", "_$1", $property->getName()))];
				}
				elseif($update) {
					continue;
				}

				// если есть внутренний метод (приоритетная обработка)
				if($class->hasMethod('set'.ucfirst($property->getName()))) {
					$object->{'set'.ucfirst($property->getName())}($value[$property->getName()] ?? null);
				}
				// Если тип свойства класс (valueObject)
				elseif($property->hasType() && class_exists($property->getType()->getName())) {
					if(is_object($value[$property->getName()])){
						$object->{$property->getName()} = $value[$property->getName()];
					}
					elseif(isset($value[$property->getName()]) && $value[$property->getName()] != ""){
						$object->{$property->getName()} = new ($property->getType()->getName())($value[$property->getName()]);
					}
					elseif(!$onlyNotNull && isset($value[$property->getName()])){
						$object->{$property->getName()} = new ($property->getType()->getName())($value[$property->getName()]);
					}
					// Значения нет и VO может быть без параметров
					elseif(!$onlyNotNull && self::isEmptyAvailable($property->getType()->getName())) {
						$object->{$property->getName()} = new ($property->getType()->getName())();
					}
				}
				// Базовые типы при наличии значения
				elseif(array_key_exists($property->getName(), $value)){
					if(!$property->hasType()){
						$object->{$property->getName()} = $value[$property->getName()];
					}
					elseif(in_array($property->getType()->getName(), ['float', 'int'])){
						if(is_numeric($value[$property->getName()])) $object->{$property->getName()} = $value[$property->getName()];
					}
					elseif($property->getType()->getName() == 'bool'){
						$object->{$property->getName()} = (bool)$value[$property->getName()];
					}
					elseif(isset($value[$property->getName()]) && $value[$property->getName()] != ""){
						$object->{$property->getName()} = $value[$property->getName()];
					}
					elseif(!$onlyNotNull && isset($value[$property->getName()])){
						$object->{$property->getName()} = $value[$property->getName()];
					}
				}
			}
			self::$stack[$object::class." #".spl_object_id($object)]['properties'] = $prop ?? [];
			self::$stack[$object::class." #".spl_object_id($object)]['values'] = $value;
			self::$stack[$object::class." #".spl_object_id($object)]['object'] = $object;
			return $object;
		}
		catch (ReflectionException $exception) {
			throw new Exception($exception->getMessage());
		}
	}


	/**
	 * @throws ReflectionException
	 */
	private static function isEmptyAvailable(string $class) : bool {
		if(isset(self::$constructorEmptyAvailableClasses[$class])) return self::$constructorEmptyAvailableClasses[$class];
		if(!$constructor = (new \ReflectionClass($class))->getConstructor()) return self::$constructorEmptyAvailableClasses[$class] = false;
		foreach($constructor->getParameters() as $param){
			if(!$param->isDefaultValueAvailable()){
				return self::$constructorEmptyAvailableClasses[$class] = false;
			}
		}
		return self::$constructorEmptyAvailableClasses[$class] = true;
	}


	/**
	 * @return array
	 */
	protected function getFillObjectStack() : array {
		return self::$stack;
	}
}
