<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Model\Traits;

use Titon\Model\Model;

/**
 * Permits a class to interact with a model.
 *
 * @package Titon\Model\Traits
 */
trait ModelAware {

    /**
     * Model object instance.
     *
     * @type \Titon\Model\Model
     */
    protected $_model;

    /**
     * Return the model.
     *
     * @return \Titon\Model\Model
     */
    public function getModel() {
        return $this->_model;
    }

    /**
     * Set the model.
     *
     * @param \Titon\Model\Model $model
     * @return \Titon\Model\Traits\ModelAware
     */
    public function setModel(Model $model) {
        $this->_model = $model;

        return $this;
    }

}