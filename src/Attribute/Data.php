<?php

namespace Rmphp\Storage\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Data {

	public function __construct(
		public bool $ignorEmpty = false,
	) {}

}
