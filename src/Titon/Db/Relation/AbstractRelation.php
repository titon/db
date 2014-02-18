<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Relation;

use Titon\Common\Augment\InfoAugment;
use Titon\Common\Base;
use Titon\Db\Exception\InvalidTableException;
use Titon\Db\Relation;
use Titon\Db\Repository;
use Titon\Db\Traits\RepositoryAware;
use \Closure;

/**
 * Provides shared functionality for relations.
 *
 * @package Titon\Db\Relation
 */
abstract class AbstractRelation extends Base implements Relation {
    use RepositoryAware;

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
     * @type \Titon\Db\Repository
     */
    protected $_relatedRepository;

    /**
     * Store the alias and class name.
     *
     * @param string $alias
     * @param string|\Titon\Db\Repository $repo
     * @param array $config
     * @throws \Titon\Db\Exception\InvalidTableException
     */
    public function __construct($alias, $repo, array $config = []) {
        parent::__construct($config);

        $this->setAlias($alias);
        $this->setClass($repo);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias() {
        return $this->getConfig('alias');
    }

    /**
     * {@inheritdoc}
     */
    public function getClass() {
        return $this->getConfig('class');
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
        return $this->getConfig('foreignKey');
    }

    /**
     * {@inheritdoc}
     */
    public function getRelatedForeignKey() {
        return $this->getConfig('relatedForeignKey');
    }

    /**
     * {@inheritdoc}
     */
    public function getRelatedRepository() {
        if ($repo = $this->_relatedRepository) {
            return $repo;
        }

        $this->setRelatedRepository($this->_loadRepository($this->getClass()));

        return $this->_relatedRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function isDependent() {
        return $this->getConfig('dependent');
    }

    /**
     * {@inheritdoc}
     */
    public function setAlias($alias) {
        $this->setConfig('alias', $alias);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setClass($class) {
        if (is_string($class)) {
            $this->setConfig('class', $class);

        } else if ($class instanceof Repository) {
            $this->setRelatedRepository($class);

        } else {
            throw new InvalidTableException(sprintf('Invalid %s relation, must be an instance of Repository or a fully qualified class name', $this->getAlias()));
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
        $this->setConfig('dependent', (bool) $state);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setForeignKey($key) {
        $this->setConfig('foreignKey', $key);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setRelatedForeignKey($key) {
        $this->setConfig('relatedForeignKey', $key);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setRelatedRepository(Repository $repo) {
        $this->_relatedRepository = $repo;

        return $this;
    }

    /**
     * Attempt to find the Repository object based on the class name.
     * If the class is not an instance of Repository, but is RepositoryAware, use that instance.
     *
     * @param string $class
     * @return \Titon\Db\Repository
     * @throws \Titon\Db\Exception\InvalidTableException
     */
    protected function _loadRepository($class) {
        $repo = new $class();

        if (!($repo instanceof Repository)) {
            if (in_array('Titon\Db\Traits\RepositoryAware', (new InfoAugment($repo))->traits())) {
                $repo = $repo->getRepository();
            } else {
                throw new InvalidTableException(sprintf('%s relation must return a Repository instance', $this->getAlias()));
            }
        }

        return $repo;
    }

}