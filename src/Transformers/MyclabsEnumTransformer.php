<?php

namespace Spatie\TypeScriptTransformer\Transformers;

use MyCLabs\Enum\Enum;
use ReflectionClass;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Structures\TypesCollection;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

class MyclabsEnumTransformer implements Transformer
{
    public function __construct(protected TypeScriptTransformerConfig $config)
    {
    }

    public function transform(ReflectionClass $class, string $name): null|TransformedType|TypesCollection
    {
        if ($class->isSubclassOf(Enum::class) === false) {
            return null;
        }

        $shouldTransformToNativeEnums = $this->config->shouldTransformToNativeEnums();
        $tsAttribute = $class->getAttributes(TypeScript::class);
        if (!empty($tsAttribute)) {
          $tsAttribute = $tsAttribute[0]->newInstance();
          if (($tsAttribute->options['nativeEnums'] ?? null) !== null) {
            $shouldTransformToNativeEnums = $tsAttribute->options['nativeEnums'];
          }
        }

        return $shouldTransformToNativeEnums
            ? $this->toEnum($class, $name)
            : $this->toType($class, $name);
    }

    protected function toEnum(ReflectionClass $class, string $name): TransformedType
    {
        /** @var \MyCLabs\Enum\Enum $enum */
        $enum = $class->getName();

        $options = array_map(
            fn ($key, $value) => "'{$key}' = '{$value}'",
            array_keys($enum::toArray()),
            $enum::toArray()
        );

        return TransformedType::create(
            $class,
            $name,
            implode(', ', $options),
            keyword: 'enum'
        );
    }

    protected function toType(ReflectionClass $class, string $name): TransformedType
    {
        /** @var \MyCLabs\Enum\Enum $enum */
        $enum = $class->getName();

        $options = array_map(
            fn (Enum $enum) => "'{$enum->getValue()}'",
            $enum::values()
        );

        return TransformedType::create(
            $class,
            $name,
            implode(' | ', $options)
        );
    }
}
