<?php

/**
 * Statement utilities.
 *
 * @package    SqlParser
 * @subpackage Utils
 */
namespace SqlParser\Utils;

use SqlParser\Parser;
use SqlParser\Statement;
use SqlParser\Token;
use SqlParser\Statements\AlterStatement;
use SqlParser\Statements\AnalyzeStatement;
use SqlParser\Statements\CallStatement;
use SqlParser\Statements\CheckStatement;
use SqlParser\Statements\ChecksumStatement;
use SqlParser\Statements\CreateStatement;
use SqlParser\Statements\DeleteStatement;
use SqlParser\Statements\DropStatement;
use SqlParser\Statements\ExplainStatement;
use SqlParser\Statements\InsertStatement;
use SqlParser\Statements\OptimizeStatement;
use SqlParser\Statements\RepairStatement;
use SqlParser\Statements\ReplaceStatement;
use SqlParser\Statements\SelectStatement;
use SqlParser\Statements\ShowStatement;
use SqlParser\Statements\UpdateStatement;

/**
 * Statement utilities.
 *
 * @category   Routines
 * @package    SqlParser
 * @subpackage Utils
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class Query
{

    /**
     * Functions that set the flag `is_func`.
     *
     * @var array
     */
    public static $FUNCTIONS = array(
        'SUM','AVG','STD','STDDEV','MIN','MAX','BIT_OR','BIT_AND'
    );

    /**
     * Gets an array with flags this statement has.
     *
     * @param Statement $statement The statement to be processed.
     * @param bool      $all       If `false`, false values will not be included.
     *
     * @return array
     */
    public static function getFlags($statement, $all = false)
    {
        $flags = array();
        if ($all) {
            $flags = array(

                /**
                 * select ... DISTINCT ...
                 */
                'distinct'      => false,

                /**
                 * drop ... DATABASE ...
                 */
                'drop_database' => false,

                /**
                 * ... GROUP BY ...
                 */
                'group'         => false,

                /**
                 * ... HAVING ...
                 */
                'having'        => false,

                /**
                 * INSERT ...
                 * or
                 * REPLACE ...
                 * or
                 * DELETE ...
                 */
                'is_affected'   => false,

                /**
                 * select ... PROCEDURE ANALYSE( ... ) ...
                 */
                'is_analyse'    => false,

                /**
                 * select COUNT( ... ) ...
                 */
                'is_count'      => false,

                /**
                 * DELETE ...
                 */
                'is_delete'     => false, // @deprecated; use `querytype`

                /**
                 * EXPLAIN ...
                 */
                'is_explain'    => false, // @deprecated; use `querytype`

                /**
                 * select ... INTO OUTFILE ...
                 */
                'is_export'     => false,

                /**
                 * select FUNC( ... ) ...
                 */
                'is_func'       => false,

                /**
                 * select ... GROUP BY ...
                 * or
                 * select ... HAVING ...
                 */
                'is_group'      => false,

                /**
                 * INSERT ...
                 * or
                 * REPLACE ...
                 * or
                 * TODO: LOAD DATA ...
                 */
                'is_insert'     => false,

                /**
                 * ANALYZE ...
                 * or
                 * CHECK ...
                 * or
                 * CHECKSUM ...
                 * or
                 * OPTIMIZE ...
                 * or
                 * REPAIR ...
                 */
                'is_maint'      => false,

                /**
                 * CALL ...
                 */
                'is_procedure'  => false,

                /**
                 * REPLACE ...
                 */
                'is_replace'    => false, // @deprecated; use `querytype`

                /**
                 * SELECT ...
                 */
                'is_select'     => false, // @deprecated; use `querytype`

                /**
                 * SHOW ...
                 */
                'is_show'       => false, // @deprecated; use `querytype`

                /**
                 * Contains a subquery.
                 */
                'is_subquery'   => false,

                /**
                 * ... JOIN ...
                 */
                'join'          => false,

                /**
                 * ... LIMIT ...
                 */
                'limit'         => false,

                /**
                 * TODO
                 */
                'offset'        => false,

                /**
                 * ... ORDER ...
                 */
                'order'         => false,

                /**
                 * The type of the query (which is usually the first keyword of
                 * the statement).
                 */
                'querytype'     => false,

                /**
                 * Whether a page reload is required.
                 */
                'reload'        => false,

                /**
                 * SELECT ... FROM ...
                 */
                'select_from'   => false,

                /**
                 * ... UNION ...
                 */
                'union'         => false
            );
        }

        if ($statement instanceof AlterStatement) {
            $flags['querytype'] = 'ALTER';
            $flags['reload'] = true;
        } else if ($statement instanceof CreateStatement) {
            $flags['querytype'] = 'CREATE';
            $flags['reload'] = true;
        } else if ($statement instanceof AnalyzeStatement) {
            $flags['querytype'] = 'ANALYZE';
            $flags['is_maint'] = true;
        } else if ($statement instanceof CheckStatement) {
            $flags['querytype'] = 'CHECK';
            $flags['is_maint'] = true;
        } else if ($statement instanceof ChecksumStatement) {
            $flags['querytype'] = 'CHECKSUM';
            $flags['is_maint'] = true;
        } else if ($statement instanceof OptimizeStatement) {
            $flags['querytype'] = 'OPTIMIZE';
            $flags['is_maint'] = true;
        } else if ($statement instanceof RepairStatement) {
            $flags['querytype'] = 'REPAIR';
            $flags['is_maint'] = true;
        } else if ($statement instanceof CallStatement) {
            $flags['querytype'] = 'CALL';
            $flags['is_procedure'] = true;
        } else if ($statement instanceof DeleteStatement) {
            $flags['querytype'] = 'DELETE';
            $flags['is_delete'] = true;
            $flags['is_affected'] = true;
        } else if ($statement instanceof DropStatement) {
            $flags['querytype'] = 'DROP';
            $flags['reload'] = true;

            if (($statement->options->has('DATABASE')
                || ($statement->options->has('SCHEMA')))
            ) {
                $flags['drop_database'] = true;
            }
        } else if ($statement instanceof ExplainStatement) {
            $flags['querytype'] = 'EXPLAIN';
            $flags['is_explain'] = true;
        } else if ($statement instanceof InsertStatement) {
            $flags['querytype'] = 'INSERT';
            $flags['is_affected'] = true;
            $flags['is_insert'] = true;
        } else if ($statement instanceof ReplaceStatement) {
            $flags['querytype'] = 'REPLACE';
            $flags['is_affected'] = true;
            $flags['is_replace'] = true;
            $flags['is_insert'] = true;
        } else if ($statement instanceof SelectStatement) {
            $flags['querytype'] = 'SELECT';
            $flags['is_select'] = true;

            if (!empty($statement->from)) {
                $flags['select_from'] = true;
            }

            if ($statement->options->has('DISTINCT')) {
                $flags['distinct'] = true;
            }

            if ((!empty($statement->group)) || (!empty($statement->having))) {
                $flags['is_group'] = true;
            }

            if ((!empty($statement->into))
                && ($statement->into->type === 'OUTFILE')
            ) {
                $flags['is_export'] = true;
            }

            foreach ($statement->expr as $expr) {
                if (!empty($expr->function)) {
                    if ($expr->function === 'COUNT') {
                        $flags['is_count'] = true;
                    } else if (in_array($expr->function, static::$FUNCTIONS)) {
                        $flags['is_func'] = true;
                    }
                }
                if (!empty($expr->subquery)) {
                    $flags['is_subquery'] = true;
                }
            }

            if ((!empty($statement->procedure))
                && ($statement->procedure->name === 'ANALYSE')
            ) {
                $flags['is_analyse'] = true;
            }

            if (!empty($statement->group)) {
                $flags['group'] = true;
            }

            if (!empty($statement->having)) {
                $flags['having'] = true;
            }

            if (!empty($statement->union)) {
                $flags['union'] = true;
            }

            if (!empty($statement->join)) {
                $flags['join'] = true;
            }

        } else if ($statement instanceof ShowStatement) {
            $flags['querytype'] = 'SHOW';
            $flags['is_show'] = true;
        } else if ($statement instanceof UpdateStatement) {
            $flags['querytype'] = 'UPDATE';
            $flags['is_affected'] = true;
        }

        if (($statement instanceof SelectStatement)
            || ($statement instanceof UpdateStatement)
            || ($statement instanceof DeleteStatement)
        ) {
            if (!empty($statement->limit)) {
                $flags['limit'] = true;
            }
            if (!empty($statement->order)) {
                $flags['order'] = true;
            }
        }

        return $flags;
    }

    /**
     * Parses a query and gets all information about it.
     *
     * @param string $query The query to be parsed.
     *
     * @return array
     */
    public static function getAll($query)
    {
        $parser = new Parser($query);

        if (!isset($parser->statements[0])) {
            return array();
        }

        $statement = $parser->statements[0];

        $ret = static::getFlags($statement, true);

        $ret['parser'] = $parser;
        $ret['statement'] = $statement;

        if ($statement instanceof SelectStatement) {
            $ret['select_tables'] = array();
            $ret['select_expr'] = array();

            // Trying to find selected tables only from the select expression.
            // Sometimes, this is not possible because the tables aren't defined
            // explicitly (e.g. SELECT * FROM film, SELECT film_id FROM film).
            foreach ($statement->expr as $expr) {
                if (!empty($expr->table)) {
                    $ret['select_tables'][] = array(
                        $expr->table,
                        !empty($expr->database) ? $expr->database : null
                    );
                } else {
                    $ret['select_expr'][] = $expr->expr;
                }
            }

            // If no tables names were found in the SELECT clause or if there
            // are expressions like * or COUNT(*), etc. tables names should be
            // extracted from the FROM clause as well.
            if ((empty($ret['select_tables'])) || (!$ret['select_expr'])) {
                foreach ($statement->from as $expr) {
                    if (!empty($expr->table)) {
                        $ret['select_tables'][] = array(
                            $expr->table,
                            !empty($expr->database) ? $expr->database : null
                        );
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * Gets the type of clause.
     *
     * @param  string $clause The clause.
     *
     * @return string
     */
    public static function getClauseType($clause)
    {
        $type = '';
        for ($i = 0, $len = strlen($clause); $i < $len; ++$i) {
            if ((empty($type)) && (ctype_space($clause[$i]))) {
                // Skipping whitespaces if we haven't started determining the
                // type.
                continue;
            }
            if (!ctype_alnum($clause[$i])) {
                // The type contains only alphanumeric characters.
                break;
            }
            // Adding character.
            $type .= $clause[$i];
        }
        return $type;
    }

    /**
     * Gets a specific clause.
     *
     * @param Statement  $statement The parsed query that has to be modified.
     * @param TokensList $list      The list of tokens.
     * @param string     $clause    The clause to be returned.
     * @param int        $type      The type of the search.
     *                              -1 for everything that was before
     *                              0 only for the clause
     *                              1 for everything after
     * @param bool       $skipFirst Whether to skip the first keyword in clause.
     *
     * @return string
     */
    public static function getClause($statement, $list, $clause, $type = 0, $skipFirst = true)
    {

        /**
         * Current location.
         * @var int
         */
        $currIdx = 0;

        /**
         * The count of brackets.
         * We keep track of them so we won't insert the clause in a subquery.
         * @var int
         */
        $brackets = 0;

        /**
         * The string to be returned.
         * @var string
         */
        $ret = '';

        /**
         * The place where the clause should be added.
         * @var int
         */
        $clauseIdx = $statement::$SECTIONS[static::getClauseType($clause)];

        for ($i = $statement->first; $i <= $statement->last; ++$i) {

            $token = $list->tokens[$i];

            if ($token->type === Token::TYPE_DELIMITER) {
                break;
            }

            if ($token->type === Token::TYPE_OPERATOR) {
                if ($token->value === '(') {
                    ++$brackets;
                } elseif ($token->value === ')') {
                    --$brackets;
                }
            }

            if ($brackets == 0) {
                // Checking if we changed sections.
                if ($token->type === Token::TYPE_KEYWORD) {
                    if (isset($statement::$SECTIONS[$token->value])) {
                        if ($statement::$SECTIONS[$token->value] >= $currIdx) {
                            $currIdx = $statement::$SECTIONS[$token->value];
                            if (($skipFirst) && ($currIdx == $clauseIdx)) {
                                // This token is skipped (not added to the old
                                // clause) because it will be replaced.
                                continue;
                            }
                        }
                    }
                }
            }

            if ((($type === -1) && ($currIdx < $clauseIdx))
                || (($type === 0) && ($currIdx === $clauseIdx))
                || (($type === 1) && ($currIdx > $clauseIdx))
            ) {
                $ret .= $token->token;
            }
        }

        return trim($ret);
    }

    /**
     * Builds a query by rebuilding the statement from the tokens list supplied
     * and replaces a clause.
     *
     * It is a very basic version of a query builder.
     *
     * @param Statement  $statement The parsed query that has to be modified.
     * @param TokensList $list      The list of tokens.
     * @param string     $clause    The clause to be replaced.
     * @param bool       $onlyType  Whether only the type of the clause should
     *                              be replaced or the entire clause.
     *
     * @return string
     */
    public static function replaceClause($statement, $list, $clause, $onlyType = false)
    {
        // TODO: Update the tokens list and the statement.

        if ($onlyType) {
            return static::getClause($statement, $list, $clause, -1, false) . ' ' .
                $clause . ' ' .
                static::getCLause($statement, $list, $clause, 0) . ' ' .
                static::getClause($statement, $list, $clause, 1, false);
        }

        return static::getClause($statement, $list, $clause, -1, false) . ' ' .
            $clause . ' ' .
            static::getClause($statement, $list, $clause, 1, false);
    }

}
