<?php

declare(strict_types=1);

namespace HackRouting\PatternParser;

use Psl;
use Psl\Dict;
use Psl\Iter;
use Psl\Vec;

use function var_export;

final class Parser
{
    private function __construct()
    {
    }

    public static function parse(string $pattern): PatternNode
    {
        $tokens = tokenize($pattern);

        [$node, $tokens] = self::parseImpl($tokens);
        Psl\invariant(Iter\is_empty($tokens), 'Tokens remaining at end of expression: %s', var_export($tokens, true));
        return $node;
    }

    /**
     * @param list<Token> $tokens
     *
     * @return array{PatternNode, list<Token>}
     */
    private static function parseImpl(array $tokens): array
    {
        $nodes = [];

        while (!Iter\is_empty($tokens)) {
            /** @var Token $token */
            $token = Iter\first($tokens);
            $type = $token->getType();
            $text = $token->getValue();

            $tokens = Vec\values(Dict\drop($tokens, 1));

            if ($type === Token::TYPE_OPEN_BRACE) {
                [$node, $tokens] = self::parseParameter($tokens);
                $nodes[] = $node;
                /**
                 * @var Token $token
                 */
                $token = Iter\first($tokens);
                Psl\invariant(
                    $token->getType() === Token::TYPE_CLOSE_BRACE,
                    'Got %s without %s',
                    Token::TYPE_OPEN_BRACE,
                    Token::TYPE_CLOSE_BRACE,
                );
                $tokens = Vec\values(Dict\drop($tokens, 1));
                continue;
            }

            if ($type === Token::TYPE_OPEN_BRACKET) {
                [$node, $tokens] = self::parseImpl($tokens);
                $nodes[] = new OptionalNode($node);
                /**
                 * @var Token $token
                 */
                $token = Iter\first($tokens);
                Psl\invariant(
                    $token->getType() === Token::TYPE_CLOSE_BRACKET,
                    'Got %s without %s',
                    Token::TYPE_OPEN_BRACKET,
                    Token::TYPE_CLOSE_BRACKET,
                );
                $tokens = Vec\values(Dict\drop($tokens, 1));
                continue;
            }

            if ($type === Token::TYPE_CLOSE_BRACKET) {
                $tokens = Vec\concat([new Token($type, $text)], $tokens);
                return array(new PatternNode($nodes), $tokens);
            }

            Psl\invariant(
                $type === Token::TYPE_STRING,
                'Unexpected token type: %s',
                $type,
            );
            $nodes[] = new LiteralNode($text);
        }

        return array(new PatternNode($nodes), $tokens);
    }

    /**
     * @param list<Token> $tokens
     *
     * @return array{ParameterNode, list<Token>}
     */
    private static function parseParameter(array $tokens): array
    {
        /**
         * @var Token $token
         */
        $token = Iter\first($tokens);
        Psl\invariant($token->getType() === Token::TYPE_STRING, 'Expected parameter to start with a name, got %s', $token->toString());
        $name = $token->getValue();
        $tokens = Vec\values(Dict\drop($tokens, 1));

        /**
         * @var Token $token
         */
        $token = Iter\first($tokens);
        if ($token->getType() === Token::TYPE_CLOSE_BRACE) {
            return [new ParameterNode($name, null), $tokens];
        }

        Psl\invariant(
            $token->getType() === Token::TYPE_COLON,
            'Expected parameter name "%s" to be followed by "%s" or "%s", got "%s"',
            $name,
            Token::TYPE_CLOSE_BRACE,
            Token::TYPE_COLON,
            $token->toString(),
        );

        $tokens = Vec\values(Dict\drop($tokens, 1));
        $regexp = '';
        $depth = 0;
        while (!Iter\is_empty($tokens)) {
            /**
             * @var Token $token
             */
            $token = Iter\first($tokens);
            if ($token->getType() === Token::TYPE_OPEN_BRACE) {
                ++$depth;
            } elseif ($token->getType() === Token::TYPE_CLOSE_BRACE) {
                if ($depth === 0) {
                    break;
                }
                --$depth;
            }
            $tokens = Vec\values(Dict\drop($tokens, 1));
            $regexp .= $token->getValue();
        }

        Psl\invariant($depth === 0, '%s without matching %s in regexp', Token::TYPE_OPEN_BRACE, Token::TYPE_CLOSE_BRACE);

        return [new ParameterNode($name, $regexp), $tokens];
    }
}
