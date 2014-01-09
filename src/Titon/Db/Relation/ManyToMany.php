<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Relation;

use Titon\Common\Registry;
use Titon\Db\Relation;
use Titon\Utility\Path;

/**
 * Represents a many-to-many table relationship.
 * Also known as a has and belongs to many.
 *
 * @link http://en.wikipedia.org/wiki/Many-to-many_%28data_model%29
 *
 * @package Titon\Db\Relation
 */
class ManyToMany extends AbstractRelation {

    /**
     * Configuration.
     *
     * @type array {
     *      @type string $junctionClass Fully qualified table class name for the junction table
     * }
     */
    protected $_config = [
        'junctionClass' => ''
    ];

    /**
     * Return the junction class name.
     *
     * @return string
     */
    public function getJunctionClass() {
        return $this->config->junctionClass;
    }

    /**
     * Return the junction table instance.
     *
     * @return \Titon\Db\Table
     */
    public function getJunctionTable() {
        $class = $this->getJunctionClass();
        $alias = Path::className($class);
        $table = $this->getTable();

        if ($table->hasObject($alias)) {
            return $table->getObject($alias);
        }

        $table->attachObject($alias, Registry::factory($class));

        return $table->getObject($alias);
    }

    /**
     * {@inheritdoc}
     */
    public function getType() {
        return Relation::MANY_TO_MANY;
    }

    /**
     * Junction records should always be deleted.
     *
     * @return bool
     */
    public function isDependent() {
        return true;
    }

    /**
     * Set the junction class name.
     *
     * @param string $class
     * @return \Titon\Db\Relation\ManyToMany
     */
    public function setJunctionClass($class) {
        $this->config->junctionClass = $class;

        return $this;
    }

}