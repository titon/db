<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Dialect;

use Titon\Utility\String;

/**
 * The Statement class represents a single SQL statement.
 * It allows for tokenization and injection of parameters,
 * making it really easy for statement building.
 *
 * @package Titon\Db\Driver\Dialect
 */
class Statement {

    /**
     * List of parameters extracted from the statement.
     *
     * @type array
     */
    protected $_params = [];

    /**
     * The SQL statement.
     *
     * @type string
     */
    protected $_statement;

    /**
     * Set the statement and tokenize the parameters.
     *
     * @param string $statement
     */
    public function __construct($statement) {
        $this->_statement = $statement;

        preg_match_all('/{([a-z0-9]+)}/i', $statement, $matches);

        $this->_params = array_map(function() {
            return '';
        }, array_flip($matches[1]));
    }

    /**
     * Return all the params.
     *
     * @return array
     */
    public function getParams() {
        return $this->_params;
    }

    /**
     * Return the statement.
     *
     * @return string
     */
    public function getStatement() {
        return $this->_statement;
    }

    /**
     * Render the statement by injecting custom parameters.
     * Merge with the default parameters to fill in any missing keys.
     *
     * @param array $params
     * @return string
     */
    public function render(array $params = []) {
        return trim(String::insert($this->getStatement(), $params + $this->getParams(), ['escape' => false])) . ';';
    }

}