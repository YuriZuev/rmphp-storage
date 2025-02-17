<?php

namespace Rmphp\Storage\Entity;

interface ValueObjectInterface {

	public function getValue();
	public function __toString(): string;

}
