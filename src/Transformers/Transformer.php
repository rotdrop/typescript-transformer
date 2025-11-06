<?php

namespace Spatie\TypeScriptTransformer\Transformers;

use ReflectionClass;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Structures\TypesCollection;

interface Transformer
{
    public function transform(ReflectionClass $class, string $name): null|TransformedType|TypesCollection;
}
