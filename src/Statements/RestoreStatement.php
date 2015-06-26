<?php

namespace SqlParser\Statements;

use SqlParser\Statements\MaintenanceStatement;

/**
 * `RESTORE` statement.
 *
 * RESTORE TABLE tbl_name [, tbl_name] ... FROM '/path/to/backup/directory'
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class RestoreStatement extends MaintenanceStatement
{

    /**
     * Options of this statement.
     *
     * @var array
     */
    public static $OPTIONS = array(

        'TABLE'                         => 1,

        'FROM'                          => array(2, 'var'),
    );

    /**
     * Restored tables.
     *
     * @var FieldFragment[]
     */
    public $tables;
}
