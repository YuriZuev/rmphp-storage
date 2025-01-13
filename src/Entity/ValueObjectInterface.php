<?php

namespace Rmphp\Storage\Entity;

interface ValueObjectInterface {

	public function get();
	public function __toString(): string;

}
