<?php

/**
 * `DELETE` statement.
 *
 * @package    SqlParser
 * @subpackage Statements
 */
namespace SqlParser\Statements;

use SqlParser\Statement;

/**
 * `DELETE` statement.
 *
 * DELETE [LOW_PRIORITY] [QUICK] [IGNORE] FROM tbl_name
 *     [PARTITION (partition_name,...)]
 *     [WHERE where_condition]
 *     [ORDER BY ...]
 *     [LIMIT row_count]
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class DeleteStatement extends Statement
{

    /**
     * Options for `DELETE` statements.
     *
     * @var array
     */
    public static $OPTIONS = array(
        'LOW_PRIORITY'                  => 1,
        'QUICK'                         => 2,
        'IGNORE'                        => 3,
    );

    /**
     * The clauses of this statement, in order.
     *
     * @see Statement::$CLAUSES
     *
     * @var array
     */
    public static $CLAUSES = array(
        'DELETE'                        => array('DELETE',      2),
        // Used for options.
        '_OPTIONS'                      => array('_OPTIONS',    1),
        'FROM'                          => array('FROM',        3),
        'PARTITION'                     => array('PARTITION',   3),
        'WHERE'                         => array('WHERE',       3),
        'ORDER BY'                      => array('ORDER BY',    3),
        'LIMIT'                         => array('LIMIT',       3),
    );

    /**
     * Tables used as sources for this statement.
     *
     * @var FieldFragment[]
     */
    public $from;

    /**
     * Partitions used as source for this statement.
     *
     * @var ArrayFragment
     */
    public $partition;

    /**
     * Conditions used for filtering each row of the result set.
     *
     * @var WhereKeyword[]
     */
    public $where;

    /**
     * Specifies the order of the rows in the result set.
     *
     * @var OrderKeyword[]
     */
    public $order;

    /**
     * Conditions used for limiting the size of the result set.
     *
     * @var LimitKeyword
     */
    public $limit;
}
