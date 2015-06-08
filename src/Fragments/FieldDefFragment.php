<?php

namespace SqlParser\Fragments;

use SqlParser\Context;
use SqlParser\Fragment;
use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * Parses the definition of a field.
 *
 * Used for parsing `CREATE TABLE` statement.
 */
class FieldDefFragment extends Fragment
{

    /**
     * All field options.
     *
     * @var array
     */
    public static $FIELD_OPTIONS = array(
        'NOT NULL'                      => 1,
        'NULL'                          => 1,
        'DEFAULT'                       => array(2, 'var'),
        'AUTO_INCREMENT'                => 3,
        'PRIMARY'                       => 4,
        'PRIMARY KEY'                   => 4,
        'UNIQUE'                        => 4,
        'UNIQUE KEY'                    => 4,
        'COMMENT'                       => array(5, 'var'),
        'COLUMN_FORMAT'                 => array(6, 'var'),
    );

    /**
     * The name of the new column.
     *
     * @var string
     */
    public $name;

    /**
     * The data type of thew new column.
     *
     * @var DataTypeFragment
     */
    public $type;

    /**
     * The array of indexes.
     *
     * @var array
     */
    public $indexes = array();

    /**
     * The options of the new field fragment.
     *
     * @var OptionsFragment
     */
    public $options;

    /**
     * @param Parser $parser
     * @param TokensList $list
     * @param array $options
     *
     * @return FieldDefFragment[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        $expr = new FieldDefFragment();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -----------------------[ ( ]------------------------> 1
         *
         *      1 -------------------[ CONSTRAINT ]-------------------> 4
         *      1 --------------------[ key type ]--------------------> 5
         *      1 -------------------[ column name ]------------------> 2
         *
         *      2 -------------------[ data type ]--------------------> 3
         *
         *      3 ---------------------[  size  ]---------------------> 3
         *      3 ---------------------[ options ]--------------------> 4
         *
         *      4 -----------------[ CONSTRAINT name ]----------------> 4
         *      4 -----------------[ CONSTRAINT type ]----------------> 5
         *
         *      5 -------------------[ index names ]------------------> 6
         *
         *      6 ------------------------[ , ]-----------------------> 1
         *      6 ------------------------[ ) ]-----------------------> -1
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

            if ($state === 0) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    $state = 1;
                }
                continue;
            } elseif ($state === 1) {
                if ($token->type === Token::TYPE_KEYWORD) {
                    if ($token->value === 'CONSTRAINT') {
                        $state = 4;
                    } elseif (isset(Context::$KEY_TYPES[$token->value])) {
                        $expr->type = $token->value;
                        $state = 5;
                    } else {
                        $parser->error('Unexpected keyword.', $token);
                        break; // TODO: Skip to the end of the query.
                    }
                } else {
                    $expr->name = $token->value;
                    $state = 2;
                }
            } elseif ($state === 2) {
                $expr->type = DataTypeFragment::parse($parser, $list);
                $state = 3;
            } elseif ($state === 3) {
                $expr->options = OptionsFragment::parse($parser, $list, static::$FIELD_OPTIONS);
                $state = 6;
            } elseif ($state === 4) {
                if (!isset(Context::$KEY_TYPES[$token->value])) {
                    $expr->name = $token->value;
                } else {
                    $expr->type = $token->value;
                    $state = 5;
                }
            } elseif ($state === 5) {
                $expr->indexes = ArrayFragment::parse($parser, $list);
                $state = 6;
            } elseif ($state === 6) {
                $ret[] = $expr;
                $expr = new FieldDefFragment();
                if ($token->value === ',') {
                    $state = 1;
                    continue;
                } elseif ($token->value === ')') {
                    ++$list->idx;
                    break;
                }
            }

            $expr->tokens[] = $token;
        }

        // Last iteration was not saved.
        if (!empty($expr->tokens)) {
            $ret[] = $expr;
        }

        --$list->idx;
        return $ret;

    }
}
