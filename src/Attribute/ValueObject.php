<?php

namespace Rmphp\Storage\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ValueObject {

	public function __construct(
		public bool $autoPropertyName = false,
		public ?string $propertyName = null,
	) {}

}
