<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver;

use Titon\Db\Driver;

/**
 * Represents a database column type.
 *
 * @package Titon\Db\Driver
 * @codeCoverageIgnore
 */
interface Type {

    const INT = 'int';
    const BIGINT = 'bigint';
    const SERIAL = 'serial';
    const BINARY = 'binary';
    const FLOAT = 'float';
    const DOUBLE = 'double';
    const DECIMAL = 'decimal';
    const BOOLEAN = 'boolean';
    const DATE = 'date';
    const TIME = 'time';
    const DATETIME = 'datetime';
    const YEAR = 'year';
    const CHAR = 'char';
    const STRING = 'string';
    const TEXT = 'text';
    const BLOB = 'blob';

    /**
     * Convert the value after it leaves the database and enters the codebase.
     *
     * @param mixed $value
     * @return mixed
     */
    public function from($value);

    /**
     * Return the binding type for this value, whether from PDO or another library.
     *
     * @return mixed
     */
    public function getBindingType();

    /**
     * Return an array of default column options.
     *
     * @return array
     */
    public function getDefaultOptions();

    /**
     * Return the type name.
     *
     * @return string
     */
    public function getName();

    /**
     * Convert the value before it enters the database.
     *
     * @param mixed $value
     * @return mixed
     */
    public function to($value);

}