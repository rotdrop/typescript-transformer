<?php

namespace Spatie\TypeScriptTransformer\Transformers;

use ReflectionClass;
use ReflectionProperty;
use Spatie\TypeScriptTransformer\Attributes\Hidden;
use Spatie\TypeScriptTransformer\Attributes\Optional;
use Spatie\TypeScriptTransformer\Attributes\TemplateParameters;
use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Structures\TypesCollection;
use Spatie\TypeScriptTransformer\TypeProcessors\DtoCollectionTypeProcessor;
use Spatie\TypeScriptTransformer\TypeProcessors\ReplaceDefaultsTypeProcessor;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;
use phpDocumentor\Reflection\Types\Nullable;

class DtoTransformer implements Transformer
{
    use TransformsTypes;

    protected TypeScriptTransformerConfig $config;

    public function __construct(TypeScriptTransformerConfig $config)
    {
        $this->config = $config;
    }

    public function transform(ReflectionClass $class, string $name): null|TransformedType|TypesCollection
    {
        if (! $this->canTransform($class)) {
            return null;
        }

        $missingSymbols = new MissingSymbolsCollection();

        $type = join([
            $this->transformProperties($class, $missingSymbols),
            $this->transformMethods($class, $missingSymbols),
            $this->transformExtra($class, $missingSymbols),
        ]);

        $templateTypes = [];
        $templateParameters = $class->getAttributes(TemplateParameters::class);
        foreach ($templateParameters as $reflectionAttribute) {
          $attribute = $reflectionAttribute->newInstance();
          $parameters = $attribute->parameters;
          if (is_string($parameters)) {
            $templateTypes[] = $parameters;
          } else {
            $templateTypes = array_merge($templateTypes, $parameters);
          }
        }

        return TransformedType::create(
            $class,
            $name,
            "{" . PHP_EOL . $type . "  }",
            $missingSymbols,
            templateTypes: $templateTypes,
        );
    }

    protected function canTransform(ReflectionClass $class): bool
    {
        return true;
    }

    protected function transformProperties(
        ReflectionClass $class,
        MissingSymbolsCollection $missingSymbols
    ): string {
        $isClassOptional = ! empty($class->getAttributes(Optional::class));
        $nullablesAreOptional = $this->config->shouldConsiderNullAsOptional();

        return array_reduce(
            $this->resolveProperties($class),
            function (string $carry, ReflectionProperty $property) use ($isClassOptional, $missingSymbols, $nullablesAreOptional) {
                $isHidden = ! empty($property->getAttributes(Hidden::class));

                if ($isHidden) {
                    return $carry;
                }

                $type = $this->reflectionToType(
                    $property,
                    $missingSymbols,
                    ...$this->typeProcessors(),
                );

                if ($type === null) {
                    return $carry;
                }

                $isOptional = $isClassOptional
                    || ! empty($property->getAttributes(Optional::class))
                    || (($property->getType()?->allowsNull() ?? ($type instanceof Nullable)) && $nullablesAreOptional);

                $transformed = $this->typeToTypeScript(
                    $type,
                    $missingSymbols,
                    $isOptional,
                    $property->getDeclaringClass()?->getName(),
                );

                $propertyName = $this->transformPropertyName($property, $missingSymbols);

                return $isOptional
                    ? "{$carry}    {$propertyName}?: {$transformed};" . PHP_EOL
                    : "{$carry}    {$propertyName}: {$transformed};" . PHP_EOL;
            },
            ''
        );
    }

    protected function transformMethods(
        ReflectionClass $class,
        MissingSymbolsCollection $missingSymbols
    ): string {
        return '';
    }

    protected function transformExtra(
        ReflectionClass $class,
        MissingSymbolsCollection $missingSymbols
    ): string {
        return '';
    }

    protected function transformPropertyName(
        ReflectionProperty $property,
        MissingSymbolsCollection $missingSymbols
    ): string {
        return $property->getName();
    }

    protected function typeProcessors(): array
    {
        return [
            new ReplaceDefaultsTypeProcessor(
                $this->config->getDefaultInlineTypeReplacements()
            ),
            new DtoCollectionTypeProcessor(),
        ];
    }

    protected function resolveProperties(ReflectionClass $class): array
    {
        $properties = array_filter(
            $class->getProperties(ReflectionProperty::IS_PUBLIC),
            fn (ReflectionProperty $property) => ! $property->isStatic()
        );

        return array_values($properties);
    }
}
