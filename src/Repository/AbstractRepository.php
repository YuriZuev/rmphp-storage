<?php

namespace Rmphp\Storage\Repository;

use Exception;
use ReflectionClass;
use Rmphp\Storage\Attribute\Entity;
use Rmphp\Storage\Attribute\GetProperty;
use Rmphp\Storage\Attribute\GetPropertyEmpty;
use Rmphp\Storage\Attribute\GetPropertyEmptyIfNull;
use Rmphp\Storage\Attribute\ValueObject;
use Rmphp\Storage\Component\AbstractDataObject;
use Rmphp\Storage\Entity\ValueObjectInterface;
use Rmphp\Storage\Exception\RepositoryException;
use Throwable;

abstract class AbstractRepository extends AbstractDataObject implements RepositoryInterface {

	/** @inheritDoc */
	public function getProperties(object $object, callable $method = null) : array {
		try{
			$class = get_class($object);
			if(!isset(self::$classes[$class])) self::$classes[$class] = new ReflectionClass($class);

			if(!isset(self::$attributeObjects[$class][0])){
				self::$attributeObjects[$class][0] = !empty(self::$classes[$class]->getAttributes(Entity::class))
					? self::$classes[$class]->getAttributes(Entity::class)[0]->newInstance()
					: new Entity();
			}
			/** @var Entity $entityAttributes */
			$entityAttributes = self::$attributeObjects[$class][0];

			$fieldValue = [];
			foreach(self::$classes[$class]->getProperties() as $property){

				if(!isset(self::$attributeObjects[$class][$property->getName()])){
					self::$attributeObjects[$class][$property->getName()] = !empty($property->getAttributes(GetProperty::class))
						? $property->getAttributes(GetProperty::class)[0]->newInstance()
						: new GetProperty();
				}
				/** @var GetProperty $getPropertyAttributes */
				$getPropertyAttributes = self::$attributeObjects[$class][$property->getName()];

				if(!empty($property->getAttributes(GetPropertyEmpty::class)) || !empty($getPropertyAttributes->empty)) continue;

				if($property->isInitialized($object)) {

					if(is_array($property->getValue($object))) continue;

					if(self::$classes[$class]->hasMethod('get'.ucfirst($property->getName()))){
						$fieldValue[$property->getName()] = $object->{'get'.ucfirst($property->getName())}($property->getValue($object));
					}
					elseif($property->hasType() && class_exists($property->getType()->getName()) && $property->getValue($object) instanceof ValueObjectInterface){

						$valueObjectClass = get_class($property->getValue($object));
						if(!isset(self::$classes[$valueObjectClass])) self::$classes[$valueObjectClass] = new ReflectionClass($valueObjectClass);

						if(!isset(self::$attributeObjects[$valueObjectClass])){
							self::$attributeObjects[$valueObjectClass] = !empty(self::$classes[$valueObjectClass]->getAttributes(ValueObject::class))
								? self::$classes[$valueObjectClass]->getAttributes(ValueObject::class)[0]->newInstance()
								: new ValueObject();
						}
						$valueObjectAttributes = self::$attributeObjects[$valueObjectClass];

						if(!empty($valueObjectAttributes->propertyName) && self::$classes[$valueObjectClass]->hasProperty($valueObjectAttributes->propertyName)){
							if(self::$classes[$valueObjectClass]->getProperty($valueObjectAttributes->propertyName)->isInitialized($property->getValue($object)))
								$fieldValue[$property->getName()] = self::$classes[$valueObjectClass]->getProperty($valueObjectAttributes->propertyName)->getValue($property->getValue($object));
						} else{
							$fieldValue[$property->getName()] = $property->getValue($object)->getValue();
						}
					}
					elseif(is_bool($property->getValue($object))){
						$fieldValue[$property->getName()] = (int)$property->getValue($object);
					}
					else{
						$fieldValue[$property->getName()] = $property->getValue($object);
					}

					if(
						!isset($fieldValue[$property->getName()])
						&& (!empty($property->getAttributes(GetPropertyEmptyIfNull::class)) || !empty($getPropertyAttributes->emptyIfNull) || !empty($entityAttributes->withoutNull))
					) continue;

					if(array_key_exists($property->getName(), $fieldValue) && false !== $fieldValue[$property->getName()]) {
						$columnName = !empty($getPropertyAttributes->keyName) ? $getPropertyAttributes->keyName : strtolower(preg_replace("'([A-Z])'", "_$1", $property->getName()));
						$out[$columnName] = $fieldValue[$property->getName()];
					}
				}
			}
			//dd($this->getAttributesObjectsCache());
			return (isset($method)) ? array_map($method, $out ?? []) : $out ?? [];
		}
		catch (\ReflectionException $exception) {
			throw new RepositoryException($exception->getMessage());
		}
	}


	/**
	 * @inheritDoc
	 * @throws RepositoryException
	 */
	public function createFromData(string $class, array|object $data, bool $withEmpty = true) : object {
		try {
			if(!isset(self::$classes[$class])) self::$classes[$class] = new ReflectionClass($class);
			return self::fillObject(self::$classes[$class], new $class, (is_object($data)) ? get_object_vars($data) : $data, false, $withEmpty);
		}
		catch (Exception $exception) {
			throw new RepositoryException($exception->getMessage());
		}
	}


	/**
	 * @inheritDoc
	 * @throws RepositoryException
	 */
	public function updateFromData(object $object, array|object $data, bool $withEmpty = true) : object {
		try {
			$class = get_class($object);
			if(!isset(self::$classes[$class])) self::$classes[$class] = new ReflectionClass($class);
			return self::fillObject(self::$classes[$class], clone $object, (is_object($data)) ? get_object_vars($data) : $data, true, $withEmpty);
		}
		catch (Exception $exception) {
			throw new RepositoryException($exception->getMessage());
		}
	}

	/**
	 * @return array
	 */
	public function getRepositoryStack() : array {
		return $this->getFillObjectStack();
	}

	/**
	 * @return array
	 */
	public function getClassesCache() : array {
		return self::$classes;
	}

	/**
	 * @return array
	 */
	public function getAttributesObjectsCache() : array {
		return self::$attributeObjects;
	}

	/**
	 * @return array
	 */
	public function getConstructorEmptyAvailableClassesCache() : array {
		return self::$constructorEmptyAvailableClasses;
	}

}
