<?php

declare(strict_types=1);

namespace HackRouting\PrefixMatching;

use Psl;

/**
 * @template TResponder
 */
final class PrefixMapOrResponder
{
    /**
     * @param null|PrefixMap<TResponder> $map
     * @param null|TResponder $responder
     */
    public function __construct(
        private ?PrefixMap $map,
        private mixed $responder,
    )
    {
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
     * @return PrefixMap<TResponder>
     */
    public function getMap(): PrefixMap
    {
        $map = $this->map;
        Psl\invariant($map !== null, 'Called getMap() when !isMap()');
        return $map;
    }

    /**
     * @return TResponder
     */
    public function getResponder(): mixed
    {
        $responder = $this->responder;
        Psl\invariant($responder !== null, 'Called getResponder() when !isResponder');
        return $responder;
    }

    /**
     * @return  array{map: null|PrefixMap<TResponder>, responder: null|TResponder}
     *
     * @internal
     */
    public function __serialize(): array
    {
        return ['map' => $this->map, 'responder' => $this->responder];
    }

    /**
     * @param array{map: null|PrefixMap<TResponder>, responder: null|TResponder} $data
     *
     * @internal
     */
    public function __unserialize(array $data): void
    {
        ['map' => $this->map, 'responder' => $this->responder] = $data;
    }
}
