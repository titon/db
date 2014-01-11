<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Relation;

use Titon\Common\Base;
use Titon\Db\Exception\InvalidTableException;
use Titon\Db\Relation;
use Titon\Db\Table;
use Titon\Db\Traits\TableAware;
use \Closure;

/**
 * Provides shared functionality for relations.
 *
 * @package Titon\Db\Relation
 */
abstract class AbstractRelation extends Base implements Relation {
    use TableAware;

    /**
     * Configuration.
     *
     * @type array {
     *      @type string $alias             The alias name to join tables on
     *      @type string $class             Fully qualified table class name to join with
     *      @type string $foreignKey        Name of the foreign key in the current table
     *      @type string $relatedForeignKey Name of the foreign key in the related table
     *      @type bool $dependent           Is the relation dependent on the parent
     * }
     */
    protected $_config = [
        'alias' => '',
        'class' => '',
        'foreignKey' => '',
        'relatedForeignKey' => '',
        'dependent' => true
    ];

    /**
     * A callback that modifies a query.
     *
     * @type \Closure
     */
    protected $_conditions;

    /**
     * Related table instance.
     *
     * @type \Titon\Db\Table
     */
    protected $_relatedTable;

    /**
     * Store the alias and class name.
     *
     * @param string $alias
     * @param string|\Titon\Db\Table $table
     * @param array $config
     * @throws \Titon\Db\Exception\InvalidTableException
     */
    public function __construct($alias, $table, array $config = []) {
        parent::__construct($config);

        $this->setAlias($alias);
        $this->setClass($table);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias() {
        return $this->config->alias;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass() {
        return $this->config->class;
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions() {
        return $this->_conditions;
    }

    /**
     * {@inheritdoc}
     */
    public function getForeignKey() {
        return $this->config->foreignKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelatedForeignKey() {
        return $this->config->relatedForeignKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelatedTable() {
        if ($table = $this->_relatedTable) {
            return $table;
        }

        $class = $this->getClass();
        $this->_relatedTable = new $class();

        return $this->_relatedTable;
    }

    /**
     * {@inheritdoc}
     */
    public function isDependent() {
        return $this->config->dependent;
    }

    /**
     * {@inheritdoc}
     */
    public function setAlias($alias) {
        $this->config->alias = $alias;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setClass($class) {
        if (is_string($class)) {
            $this->config->class = $class;

        } else if ($class instanceof Table) {
            $this->setRelatedTable($class);

        } else {
            throw new InvalidTableException(sprintf('Invalid %s relation, must be an instance of Table or a fully qualified class name', $this->getAlias()));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setConditions(Closure $callback) {
        $this->_conditions = $callback;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDependent($state) {
        $this->config->dependent = (bool) $state;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setForeignKey($key) {
        $this->config->foreignKey = $key;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setRelatedForeignKey($key) {
        $this->config->relatedForeignKey = $key;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setRelatedTable(Table $table) {
        $this->_relatedTable = $table;

        return $this;
    }

}