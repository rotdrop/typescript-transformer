<?php

namespace Spatie\TypeScriptTransformer\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class TemplateParameters
{
    public array $parameters;

    public function __construct(string|array $parameters)
    {
        $this->parameters = array_map(
            fn(string $parameter) => str_replace('\\', '.', $parameter),
            is_string($parameters) ? [$parameters] : $parameters,
        );
    }
}
