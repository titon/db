<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Query;

use Titon\Db\Driver\Dialect;
use Titon\Db\Traits\AliasAware;
use \Serializable;
use \JsonSerializable;

/**
 * The Join class represents meta data for an additional table to join records on.
 *
 * @link http://mysqljoin.com/
 * @link http://www.codeproject.com/Articles/33052/Visual-Representation-of-SQL-Joins
 * @link http://www.codinghorror.com/blog/2007/10/a-visual-explanation-of-sql-joins.html
 *
 * @package Titon\Db\Query
 */
class Join implements Serializable, JsonSerializable {
    use AliasAware;

    const LEFT = Dialect::JOIN_LEFT; // Use left join as reference table
    const RIGHT = Dialect::JOIN_RIGHT; // Use right join as reference table
    const INNER = Dialect::JOIN_INNER; // Return records that have matching rows in each join
    const OUTER = Dialect::JOIN_OUTER; // Return records that have no matching rows in each join
    const STRAIGHT = Dialect::JOIN_STRAIGHT; // Similar to INNER but forces reading of left table first

    /**
     * The conditions to join the tables on.
     * The key is the primary table field and the value is the join field.
     *
     * @type array
     */
    protected $_conditions = [];

    /**
     * The fields to query for. An empty array will query all fields.
     *
     * @type string[]
     */
    protected $_fields = [];

    /**
     * The database table.
     *
     * @type string
     */
    protected $_table;

    /**
     * The type of join.
     *
     * @type string
     */
    protected $_type;

    /**
     * Set the join type.
     *
     * @param string $type
     */
    public function __construct($type) {
        $this->_type = $type;
    }

    /**
     * Set the list of fields to return.
     *
     * @param string|array $fields
     * @return \Titon\Db\Query\Join
     */
    public function fields($fields) {
        if (!is_array($fields)) {
            $fields = func_get_args();
        }

        $this->_fields = $fields;

        return $this;
    }

    /**
     * Set the table to join against.
     *
     * @param string $repo
     * @param string $alias
     * @return \Titon\Db\Query\Join
     */
    public function from($repo, $alias = null) {
        $this->_table = (string) $repo;
        $this->asAlias($alias);

        return $this;
    }

    /**
     * Return the list of fields.
     *
     * @return string[]
     */
    public function getFields() {
        return $this->_fields;
    }

    /**
     * Return the table name.
     *
     * @return string
     */
    public function getTable() {
        return $this->_table;
    }

    /**
     * Return the type of join.
     *
     * @return int
     */
    public function getType() {
        return $this->_type;
    }

    /**
     * Return the on conditions.
     *
     * @return array
     */
    public function getOn() {
        return $this->_conditions;
    }

    /**
     * Set the conditions to join on.
     *
     * @param string $foreignKey
     * @param string $key
     * @return \Titon\Db\Query\Join
     */
    public function on($foreignKey, $key = null) {
        if (is_array($foreignKey)) {
            $this->_conditions = array_replace($this->_conditions, $foreignKey);
        } else {
            $this->_conditions[$foreignKey] = $key;
        }

        return $this;
    }

    /**
     * Serialize the function.
     *
     * @return string
     */
    public function serialize() {
        return serialize($this->jsonSerialize());
    }

    /**
     * Reconstruct the function once unserialized.
     *
     * @param string $data
     */
    public function unserialize($data) {
        $data = unserialize($data);

        $this->_alias = $data['alias'];
        $this->_type = $data['type'];
        $this->_table = $data['table'];
        $this->_fields = $data['fields'];
        $this->_conditions = $data['on'];
    }

    /**
     * Return all data for serialization.
     *
     * @return array
     */
    public function jsonSerialize() {
        return [
            'table' => $this->getTable(),
            'alias' => $this->getAlias(),
            'fields' => $this->getFields(),
            'type' => $this->getType(),
            'on' => $this->getOn()
        ];
    }

}