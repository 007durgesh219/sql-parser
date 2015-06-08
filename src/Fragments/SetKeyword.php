<?php

namespace SqlParser\Fragments;

use SqlParser\Fragment;
use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * `SET` keyword parser.
 */
class SetKeyword extends Fragment
{

    /**
     * The name of the column that is being updated.
     *
     * @var string
     */
    public $column;

    /**
     * The new value.
     *
     * @var string
     */
    public $value;

    /**
     * @param Parser $parser
     * @param TokensList $list
     * @param array $options
     *
     * @return SetKeyword[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        $expr = new SetKeyword();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -------------------[ field name ]--------------------> 1
         *
         *      1 ------------------------[ , ]------------------------> 0
         *      1 ----------------------[ value ]----------------------> 1
         *
         * @var int
         */
        $state = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /** @var Token Token parsed at this moment. */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === Token::TYPE_DELIMITER) {
                break;
            }

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                continue;
            }

            // No keyword is expected.
            if ($token->type === Token::TYPE_KEYWORD) {
                break;
            }

            if ($token->type === Token::TYPE_OPERATOR) {
                if ($token->value === ',') {
                    $ret[] = $expr;
                    $expr = new SetKeyword();
                    $state = 0;
                    continue;
                } elseif ($token->value === '=') {
                    $state = 1;
                }
            }

            $expr->tokens[] = $token;
            if ($state === 0) {
                $expr->column .= $token->value;
            } else { // } else if ($state === 1) {
                $expr->value = $token->value;
            }

        }

        // Last iteration was not saved.
        if (!empty($expr->tokens)) {
            $ret[] = $expr;
        }

        --$list->idx;
        return $ret;
    }
}
