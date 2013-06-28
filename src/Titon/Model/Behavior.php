<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

/**
 * A Behavior can be attached to a model and triggered during callbacks to modify functionality.
 *
 * @package Titon\Model
 */
interface Behavior {

	/**
	 * Return the behavior alias name.
	 *
	 * @return string
	 */
	public function getAlias();

	/**
	 * Return the current model.
	 *
	 * @return \Titon\Model\Model
	 */
	public function getModel();

	/**
	 * Set the model this behavior is attached to.
	 *
	 * @param \Titon\Model\Model $model
	 * @return \Titon\Model\Behavior
	 */
	public function setModel(Model $model);

}