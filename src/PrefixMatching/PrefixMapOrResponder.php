<?php

declare(strict_types=1);

namespace HackRouting\PrefixMatching;

use Psl;

/**
 * @template T
 */
final class PrefixMapOrResponder
{
    /**
     * @param null|PrefixMap<T> $map
     * @param null|T $responder
     */
    public function __construct(
        private ?PrefixMap $map,
        private mixed $responder,
    ) {
        Psl\invariant(
            ($map === null) !== ($responder === null),
            'Must specify map *or* responder',
        );
    }

    public function isMap(): bool
    {
        return $this->map !== null;
    }

    public function isResponder(): bool
    {
        return $this->responder !== null;
    }

    /**
     * @return PrefixMap<T>
     */
    public function getMap(): PrefixMap
    {
        $map = $this->map;
        Psl\invariant($map !== null, 'Called getMap() when !isMap()');
        return $map;
    }

    /**
     * @return T
     */
    public function getResponder(): mixed
    {
        $responder = $this->responder;
        Psl\invariant($responder !== null, 'Called getResponder() when !isResponder');
        return $responder;
    }

    /**
     * @return array<string, array<string, mixed>>|T|null
     */
    public function getSerializable(): mixed
    {
        if ($this->isMap()) {
            return $this->getMap()->getSerializable();
        }
        return $this->responder;
    }
}
