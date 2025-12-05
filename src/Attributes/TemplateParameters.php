<?php

namespace Spatie\TypeScriptTransformer\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class TemplateParameters
{
    public array | string $parameters;

    public function __construct(string|array $parameters)
    {
        $this->parameters = $parameters;
    }
}
