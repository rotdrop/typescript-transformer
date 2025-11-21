<?php

namespace Spatie\TypeScriptTransformer\Collectors;

use BackedEnum;
use ReflectionClass;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Structures\TypesCollection;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;
use Spatie\TypeScriptTransformer\TypeReflectors\ClassTypeReflector;

class EnumCollector extends DefaultCollector
{
    protected function shouldCollect(ClassTypeReflector $reflector): bool
    {
        $class = $reflector->getReflectionClass();

        $transformers = array_map('get_class', $this->config->getTransformers());

        $hasEnumTransformer = \count(
            array_filter($transformers, function (string $transformer) {
                if ($transformer === EnumTransformer::class) {
                    return true;
                }

                return is_subclass_of($transformer, EnumTransformer::class);
            }),
        ) > 0;

        if (! $hasEnumTransformer) {
            return false;
        }

        if (! $class->implementsInterface(BackedEnum::class)) {
            return false;
        }

        return true;
    }
}
