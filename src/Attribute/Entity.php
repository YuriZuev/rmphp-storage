<?php

namespace Rmphp\Storage\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Entity {

	public function __construct(
		public bool $withoutEmpty = false,
	) {}

}
