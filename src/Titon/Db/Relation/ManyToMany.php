<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Relation;

use Titon\Db\Exception\InvalidTableException;
use Titon\Db\Relation;
use Titon\Db\Repository;
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
     *      @type string $junctionAlias Alias name based on class name
     *      @type string $junctionClass Fully qualified class name for the junction repository
     * }
     */
    protected $_config = [
        'junctionAlias' => '',
        'junctionClass' => ''
    ];

    /**
     * Junction repository instance.
     *
     * @type \Titon\Db\Repository
     */
    protected $_junctionRepository;

    /**
     * Return the junction alias name.
     *
     * @return string
     */
    public function getJunctionAlias() {
        return $this->config->junctionAlias;
    }

    /**
     * Return the junction class name.
     *
     * @return string
     */
    public function getJunctionClass() {
        return $this->config->junctionClass;
    }

    /**
     * Return the junction repository instance.
     *
     * @return \Titon\Db\Repository
     */
    public function getJunctionRepository() {
        if ($repo = $this->_junctionRepository) {
            return $repo;
        }

        $this->setJunctionRepository($this->_loadRepository($this->getJunctionClass()));

        return $this->_junctionRepository;
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
     * Set the junction alias.
     *
     * @param string $alias
     * @return \Titon\Db\Relation\ManyToMany
     */
    public function setJunctionAlias($alias) {
        $this->config->junctionAlias = $alias;

        return $this;
    }

    /**
     * Set the junction class name.
     *
     * @param string|\Titon\Db\Repository $class
     * @return \Titon\Db\Relation\ManyToMany
     * @throws \Titon\Db\Exception\InvalidTableException
     */
    public function setJunctionClass($class) {
        if (is_string($class)) {
            $this->config->junctionClass = $class;

        } else if ($class instanceof Repository) {
            $this->setJunctionRepository($class);
            $class = get_class($class);

        } else {
            throw new InvalidTableException(sprintf('Invalid junction relation for %s, must be an instance of Repository or a fully qualified class name', $this->getAlias()));
        }

        $this->setJunctionAlias(Path::className($class));

        return $this;
    }

    /**
     * Set the junction repository as an alias on the primary repository.
     *
     * @param \Titon\Db\Repository $repo
     * @return \Titon\Db\Relation\ManyToMany
     */
    public function setJunctionRepository(Repository $repo) {
        $this->_junctionRepository = $repo;
        $this->getRepository()->attachObject($this->getJunctionAlias(), $repo);

        return $this;
    }

}