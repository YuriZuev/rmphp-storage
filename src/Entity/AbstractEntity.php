<?php

namespace Rmphp\Storage\Entity;

abstract class AbstractEntity implements EntityInterface {

	/**
	 * @return int|null
	 */
	public function getId(): mixed {
		return (isset($this->id)) ? (($this->id instanceof ValueObjectInterface) ? $this->id->get() : $this->id) : null;
	}

}
