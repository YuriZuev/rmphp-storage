<?php

namespace Rmphp\Storage\Component;

use Exception;
use ReflectionClass;
use ReflectionException;
use Rmphp\Storage\Attribute\Entity;

abstract class AbstractDataObject {

	private static array $stack  = [];
	protected static array $constructorEmptyAvailableClasses = [];
	protected static array $classes = [];
	protected static array $attributeObjects = [];

	/**
	 * @param ReflectionClass $class
	 * @param object $object
	 * @param array $data
	 * @param bool $update
	 * @param bool $withEmpty
	 * @return mixed
	 * @throws Exception
	 */
	protected static function fillObject(ReflectionClass $class, object $object, array $data, bool $update = false, bool $withEmpty = true) : mixed {
		try {
			if(!isset(self::$attributeObjects[$class->getName()][0])){
				self::$attributeObjects[$class->getName()][0] = !empty($class->getAttributes(Entity::class))
					? $class->getAttributes(Entity::class)[0]->newInstance()
					: new Entity();
			}
			/** @var Entity $entityAttributes */
			$entityAttributes = self::$attributeObjects[$class->getName()][0];

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
					$case[$property->getName()] = 'Method set'.ucfirst($property->getName());
				}
				// Если тип свойства класс (valueObject)
				elseif($property->hasType() && class_exists($property->getType()->getName())) {
					// значение объект
					if(is_object($value[$property->getName()])){
						$object->{$property->getName()} = $value[$property->getName()];
						$case[$property->getName()] = 'VO: Object';
					}
					// значение есть и не пустое
					elseif(isset($value[$property->getName()]) && $value[$property->getName()] !== ""){
						$object->{$property->getName()} = new ($property->getType()->getName())($value[$property->getName()]);
						$case[$property->getName()] = 'VO: NewInstance';
					}
					elseif(($withEmpty && empty($entityAttributes->withoutEmpty)) && array_key_exists($property->getName(), $value)){
						$object->{$property->getName()} = new ($property->getType()->getName())($value[$property->getName()]);
						$case[$property->getName()] = 'VO: NewInstance withEmpty';
					}
					// Значения нет и VO может быть без параметров
					elseif(($withEmpty && empty($entityAttributes->withoutEmpty)) && self::isEmptyAvailable($property->getType()->getName())) {
						$object->{$property->getName()} = new ($property->getType()->getName())();
						$case[$property->getName()] = 'VO: Without params';
					}

				}
				// Базовые типы при наличии значения
				elseif(array_key_exists($property->getName(), $value)){
					if(!$property->hasType()){
						$object->{$property->getName()} = $value[$property->getName()];
						$case[$property->getName()] = 'Base: Hasn`t type';
					}
					elseif(in_array($property->getType()->getName(), ['float', 'int'])){
						if(is_numeric($value[$property->getName()])) $object->{$property->getName()} = $value[$property->getName()];
						$case[$property->getName()] = 'Base: Number';
					}
					elseif($property->getType()->getName() == 'bool'){
						$object->{$property->getName()} = (bool)$value[$property->getName()];
						$case[$property->getName()] = 'Base: Boolean';
					}
					elseif(isset($value[$property->getName()])){
						$object->{$property->getName()} = $value[$property->getName()];
						$case[$property->getName()] = 'Base: NotNull';
					}
					elseif($property->getType()->allowsNull()){
						$object->{$property->getName()} = $value[$property->getName()];
						$case[$property->getName()] = 'Base: Null';
					}
				}
			}
			self::$stack[$object::class." #".spl_object_id($object)]['properties'] = $prop ?? [];
			self::$stack[$object::class." #".spl_object_id($object)]['values'] = $value;
			self::$stack[$object::class." #".spl_object_id($object)]['matchCase'] = $case ?? [];
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
