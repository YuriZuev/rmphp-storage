<?php

namespace Rmphp\Storage\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Property {

	public function __construct(
		public ?string $keyName = null,
		public bool $noReturn = false,
		public bool $noReturnIfNull = false,
	) {}

}
