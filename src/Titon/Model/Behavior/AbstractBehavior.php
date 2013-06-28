<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Behavior;

use Titon\Common\Base;
use Titon\Model\Behavior;
use Titon\Model\Model;
use Titon\Model\Query;

/**
 * Provides shared functionality for behaviors.
 *
 * @package Titon\Model\Behavior
 */
abstract class AbstractBehavior extends Base implements Behavior {

	/**
	 * Model object.
	 *
	 * @type \Titon\Model\Model
	 */
	protected $_model;

	/**
	 * {@inheritdoc}
	 */
	public function getAlias() {
		return str_replace('Behavior', '', $this->info->shortClassName);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getModel() {
		return $this->_model;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setModel(Model $model) {
		$this->_model = $model;

		return $this;
	}

}