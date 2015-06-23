<?php

namespace SqlParser\Fragments;

use SqlParser\Context;
use SqlParser\Fragment;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * Parses a data type.
 *
 * @category   Fragments
 * @package    SqlParser
 * @subpackage Fragments
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class DataTypeFragment extends Fragment
{

    /**
     * All data type options.
     *
     * @var array
     */
    public static $DATA_TYPE_OPTIONS = array(
        'BINARY'                        => 1,
        'CHARACTER SET'                 => array(2, 'var'),
        'CHARSET'                       => array(2, 'var'),
        'COLLATE'                       => 3,
        'UNSIGNED'                      => 4,
        'ZEROFILL'                      => 5,
    );

    /**
     * The name of the data type.
     *
     * @var string
     */
    public $name;

    /**
     * The parameters of this data type.
     *
     * Some data types have no parameters.
     * Numeric types might have parameters for the maximum number of digits,
     * precision, etc.
     * String ypes might have parameters for the maxium length stored.
     * `ENUM` and `SET` have parameters for possible values.
     *
     * For more information, check the MySQL manual.
     *
     * @var array
     */
    public $parameters = array();

    /**
     * The options of this data type.
     *
     * @var OptionsFragment
     */
    public $options;

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return DataTypeFragment[]
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new DataTypeFragment();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 -------------------[ data type ]--------------------> 1
         *
         *      1 ----------------[ size and options ]----------------> 2
         *
         * @var int
         */
        $state = 0;

        for (; $list->idx < $list->count; ++$list->idx) {

            /**
             * Token parsed at this moment.
             * @var Token
             */
            $token = $list->tokens[$list->idx];

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                continue;
            }

            if ($state === 0) {
                $ret->name = strtoupper($token->value);
                if (($token->type !== Token::TYPE_KEYWORD) || (!($token->flags & Token::FLAG_KEYWORD_DATA_TYPE))) {
                    $parser->error('Unrecognized data type.', $token);
                }
                $state = 1;
            } elseif ($state === 1) {
                if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    $size = ArrayFragment::parse($parser, $list);
                    ++$list->idx;
                    $ret->parameters = (($ret->name === 'ENUM') || ($ret->name === 'SET')) ?
                        $size->raw : $size->values;
                }
                $ret->options = OptionsFragment::parse($parser, $list, static::$DATA_TYPE_OPTIONS);
                ++$list->idx;
                break;
            }

        }

        if (empty($ret->name)) {
            return null;
        }

        --$list->idx;
        return $ret;
    }
}
