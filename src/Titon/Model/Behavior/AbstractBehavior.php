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
use Titon\Model\Traits\ModelAware;

/**
 * Provides shared functionality for behaviors.
 *
 * @package Titon\Model\Behavior
 */
abstract class AbstractBehavior extends Base implements Behavior {
	use ModelAware;

	/**
	 * {@inheritdoc}
	 */
	public function getAlias() {
		return str_replace('Behavior', '', $this->info->shortClassName);
	}

	/**
	 * {@inheritdoc}
	 */
	public function preDelete($id, &$cascade) {
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function preFetch(Query $query, $fetchType) {
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function preSave($id, array $data) {
		return $data;
	}

	/**
	 * {@inheritdoc}
	 */
	public function postDelete($id) {
		return;
	}

	/**
	 * {@inheritdoc}
	 */
	public function postFetch(array $results, $fetchType) {
		return $results;
	}

	/**
	 * {@inheritdoc}
	 */
	public function postSave($id, $created = false) {
		return;
	}

}