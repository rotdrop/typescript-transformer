<?php

namespace Spatie\TypeScriptTransformer\Attributes;

use Attribute;

#[Attribute]
class TypeScript
{
    public ?string $name;

    public ?array $options;

    public function __construct(?string $name = null, ?array $options = null)
    {
        $this->name = $name;
        $this->options = $options;
    }
}
