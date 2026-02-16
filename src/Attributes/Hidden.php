<?php

namespace Spatie\TypeScriptTransformer\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY|Attribute::TARGET_CLASS_CONSTANT)]
class Hidden
{
}
