<?php

declare(strict_types=1);

namespace HackRouting\UriPattern;

use Psl;
use Psl\Iter;
use Psl\Str;
use Psl\Type;
use Psl\Vec;
use HackRouting\Parameter\RequestParameter;
use HackRouting\Parameter\TypedUriParameter;

abstract class UriBuilderBase
{
    /**
     * @var list<UriPatternPart>
     */
    protected array $parts;

    /**
     * @var array<string, RequestParameter>
     */
    protected array $parameters;

    /**
     * @var array<string, string>
     */
    private array $values = [];

    /**
     * @param iterable<UriPatternPart> $parts
     */
    public function __construct(iterable $parts)
    {
        $this->parts = Vec\values($parts);
        $parameters = [];
        foreach ($parts as $part) {
            if (!$part instanceof RequestParameter) {
                continue;
            }

            $parameters[$part->getName()] = $part;
        }

        $this->parameters = $parameters;
    }

    final protected function getPathImpl(): string
    {
        $uri = '';
        foreach ($this->parts as $part) {
            if ($part instanceof UriPatternLiteral) {
                $uri .= $part->getValue();
                continue;
            }

            Psl\invariant($part instanceof RequestParameter, 'expecting all UriPatternParts to be literals or parameters, got %s', $part::class);

            if ($uri === '') {
                $uri = '/';
            }

            $name = $part->getName();
            Psl\invariant(Iter\contains_key($this->values, $name), 'Parameter "%s" must be set', $name);
            $uri .= $this->values[$name];
        }

        Psl\invariant($uri[0] === '/', "Path '%s' does not start with '/'", $uri);

        return $uri;
    }

    /**
     * @template T
     *
     * @param class-string<TypedUriParameter<T>> $parameter_type
     * @param T $value
     */
    final protected function setValue(
        string $parameter_type,
        string $name,
        mixed $value,
    ): static {
        $part = $this->parameters[$name] ?? null;
        Psl\invariant(
            $part !== null,
            '%s is not a valid parameter - expected one of [%s]',
            $name,
            Str\join(Vec\map(Vec\keys($this->parameters), fn (string $x): string => "'" . $x . "'"), ', '),
        );

        /** @var TypedUriParameter<T> $part */
        Psl\invariant(
            $part instanceof $parameter_type,
            'Expected %s to be a %s, got a %s',
            $name,
            $parameter_type,
            $part::class,
        );

        Psl\invariant(
            !Iter\contains_key($this->values, $name),
            'trying to set %s twice',
            $name,
        );

        $this->values[$name] = $part->getUriFragment($value);
        return $this;
    }
}
