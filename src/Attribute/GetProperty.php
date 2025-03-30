<?php

namespace Rmphp\Storage\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class GetProperty {

	public function __construct(
		public ?string $keyName = null,
		public bool $empty = false,
		public bool $emptyIfNull = false,
	) {}

}
