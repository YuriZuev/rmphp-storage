<?php

namespace Rmphp\Storage\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ValueObjectPropertyName {

	public function __construct(
		public ?string $name = null,
	) {}

}
