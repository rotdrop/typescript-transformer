<?php

namespace Spatie\TypeScriptTransformer\Actions;

use Exception;
use Generator;
use ReflectionClass;
use Spatie\TypeScriptTransformer\Exceptions\NoAutoDiscoverTypesPathsDefined;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Structures\TypesCollection;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;
use Symfony\Component\Finder\Finder;

class ResolveTypesCollectionAction
{
    protected Finder $finder;

    /** @var \Spatie\TypeScriptTransformer\Collectors\Collector[] */
    protected array $collectors;

    protected TypeScriptTransformerConfig $config;

    public function __construct(Finder $finder, TypeScriptTransformerConfig $config)
    {
        $this->finder = $finder;

        $this->config = $config;

        $this->collectors = $config->getCollectors();
    }

    public function execute(): TypesCollection
    {
        $collection = new TypesCollection();

        $paths = $this->config->getAutoDiscoverTypesPaths();
        $exclude = $this->config->getAutoDiscoverExcludePaths();

        if (empty($paths)) {
            throw NoAutoDiscoverTypesPathsDefined::create();
        }

        foreach ($this->resolveIterator($paths, $exclude) as $class) {
            $transformedType = $this->resolveTransformedType($class);

            if ($transformedType === null) {
                continue;
            }

            if ($transformedType instanceof TypesCollection) {
                foreach ($transformedType as $key => $type) {
                    $collection[$key] = $type;
                }
            } else {
                $collection[] = $transformedType;
            }
        }

        return $collection;
    }

    protected function resolveIterator(array $paths, array $exclude): Generator
    {
        $paths = array_map(
            fn (string $path) => is_dir($path) ? $path : dirname($path),
            $paths
        );

        foreach ($this->finder->files()->in($paths) as $fileInfo) {
            $path = $fileInfo->getPathname();
            foreach ($exclude as $dir) {
                if (str_starts_with($path, $dir)) {
                    continue 2;
                }
            }
            try {
                $classes = (new ResolveClassesInPhpFileAction())->execute($fileInfo);

                foreach ($classes as $name) {
                    yield $name => new ReflectionClass($name);
                }
            } catch (Exception $exception) {
                echo $path . PHP_EOL;
                throw $exception;
            }
        }
    }

    protected function resolveTransformedType(ReflectionClass $class): null|TransformedType|TypesCollection
    {
        foreach ($this->collectors as $collector) {
            $transformedType = $collector->getTransformedType($class);

            if ($transformedType !== null) {
                return $transformedType;
            }
        }

        return null;
    }
}
