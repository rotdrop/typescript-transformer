<?php

namespace Spatie\TypeScriptTransformer\Structures;

use ReflectionClass;

class TransformedType
{
    public ReflectionClass $reflection;

    public ?string $name = null;

    public ?array $templateTypes = null;

    public string $transformed;

    public MissingSymbolsCollection $missingSymbols;

    public bool $isInline;

    public string $keyword;

    public bool $trailingSemicolon;

    public static function create(
        ReflectionClass $class,
        string $name,
        string $transformed,
        ?MissingSymbolsCollection $missingSymbols = null,
        bool $inline = false,
        string $keyword = 'type',
        bool $trailingSemicolon = true,
        ?array $templateTypes = null,
    ): self {
        return new self($class, $name, $transformed, $missingSymbols ?? new MissingSymbolsCollection(), $inline, $keyword, $trailingSemicolon, $templateTypes);
    }

    public static function createInline(
        ReflectionClass $class,
        string $transformed,
        ?MissingSymbolsCollection $missingSymbols = null
    ): self {
        return new self($class, null, $transformed, $missingSymbols ?? new MissingSymbolsCollection(), true);
    }

    public function __construct(
        ReflectionClass $class,
        ?string $name,
        string $transformed,
        MissingSymbolsCollection $missingSymbols,
        bool $isInline,
        string $keyword = 'type',
        bool $trailingSemicolon = true,
        ?array $templateTypes = null,
    ) {
        $this->reflection = $class;
        $this->name = $name;
        $this->transformed = $transformed;
        $this->missingSymbols = $missingSymbols;
        $this->isInline = $isInline;
        $this->keyword = $keyword;
        $this->trailingSemicolon = $trailingSemicolon;
        $this->templateTypes = $templateTypes;
    }

    public function getNamespaceSegments(): array
    {
        if ($this->isInline === true) {
            return [];
        }

        $namespace = $this->reflection->getNamespaceName();

        if (empty($namespace)) {
            return [];
        }

        return explode('\\', $namespace);
    }

    public function getTypeScriptName($fullyQualified = true): string
    {
        if (! $fullyQualified) {
            return $this->name ?? '';
        }

        $segments = array_merge(
            $this->getNamespaceSegments(),
            [$this->name]
        );

        return implode('.', $segments);
    }

    public function replaceSymbol(string $class, string $replacement): void
    {
        $this->missingSymbols->remove($class);

        $this->transformed = str_replace(
            "{%{$class}%}",
            $replacement,
            $this->transformed
        );
    }

    public function toString(): string
    {
        if ($this->templateTypes && $this->keyword !== 'enum') {
            $name = $this->name . '<' . implode(', ', $this->templateTypes) . '>';
        } else {
            $name = $this->name;
        }
        $output = match ($this->keyword) {
            'enum' => "enum {$name} { {$this->transformed} }",
            'interface' => "interface {$name} {$this->transformed}",
            default => "type {$name} = {$this->transformed}",
        };

        return $output . ($this->trailingSemicolon ? ';' : '');
    }
}
