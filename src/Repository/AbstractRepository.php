<?php

namespace Rmphp\Storage\Repository;

use Exception;
use ReflectionClass;
use ReflectionException;
use Rmphp\Storage\Component\AbstractDataObject;
use Rmphp\Storage\Entity\ValueObjectInterface;
use Rmphp\Storage\Exception\RepositoryException;

abstract class AbstractRepository extends AbstractDataObject implements RepositoryInterface {

	private static array $classes = [];

	/** @inheritDoc */
	public function getProperties(object $object, callable $method = null) : array {
		try{
			$class = get_class($object);
			if(!isset(self::$classes[$class])) self::$classes[$class] = new ReflectionClass($class);

			$fieldValue = [];
			foreach(self::$classes[$class]->getProperties() as $property){
				if(!$property->isInitialized($object) || is_array($property->getValue($object))) continue;

				if(self::$classes[$class]->hasMethod('get'.ucfirst($property->getName()))){
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

				if(array_key_exists($property->getName(), $fieldValue) && false !== $fieldValue[$property->getName()]) {
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
	 * @inheritDoc
	 * @throws RepositoryException
	 */
	public function createFromData(string $class, array|object $data, bool $withNull = true) : object {
		try {
			if(!isset(self::$classes[$class])) self::$classes[$class] = new ReflectionClass($class);
			return self::fillObject(self::$classes[$class], new $class, (is_object($data)) ? get_object_vars($data) : $data, false, $withNull);
		}
		catch (Exception $exception) {
			throw new RepositoryException($exception->getMessage());
		}
	}


	/**
	 * @inheritDoc
	 * @throws RepositoryException
	 */
	public function updateFromData(object $object, array|object $data, bool $withNull = true) : object {
		try {
			$class = get_class($object);
			if(!isset(self::$classes[$class])) self::$classes[$class] = new ReflectionClass($class);
			return self::fillObject(self::$classes[$class], clone $object, (is_object($data)) ? get_object_vars($data) : $data, true, $withNull);
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
	public function getClassesCash() : array {
		return self::$classes;
	}

}
