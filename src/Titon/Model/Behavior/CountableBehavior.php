<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Behavior;

use Titon\Common\Traits\Cacheable;
use Titon\Event\Event;
use Titon\Model\Exception\InvalidArgumentException;
use Titon\Model\Relation;
use Titon\Model\Relation\ManyToMany;
use Titon\Model\Relation\ManyToOne;
use \Closure;

/**
 * The CountableBehavior provides a way for many-to-one|many relations to track a count of how many related records exist.
 * Each time a record is created, updated or deleted, the count will be updated in the related record.
 *
 * @package Titon\Model\Behavior
 */
class CountableBehavior extends AbstractBehavior {
	use Cacheable;

	/**
	 * List of defined counter settings.
	 *
	 * @type array
	 */
	protected $_counters = [];

	/**
	 * Add a counter for a relation.
	 *
	 * @param string|\Titon\Model\Relation $alias
	 * @param string $field
	 * @param \Closure $scope
	 * @return \Titon\Model\Behavior\CountableBehavior
	 * @throws \Titon\Model\Exception\InvalidArgumentException
	 */
	public function addCounter($alias, $field, Closure $scope = null) {
		if ($alias instanceof Relation) {
			$relation = $alias;
			$alias = $relation->getAlias();
		} else {
			$relation = $this->getModel()->getRelation($alias);
		}

		if (!in_array($relation->getType(), [Relation::MANY_TO_ONE, Relation::MANY_TO_MANY])) {
			throw new InvalidArgumentException(sprintf('Invalid relation %s, only many-to-one or many-to-many relationships permitted', $alias));
		}

		$this->_counters[$alias] = [
			'field' => $field,
			'scope' => $scope
		];

		return $this;
	}

	/**
	 * Fetch records about to be deleted since they do not exist in postDelete().
	 *
	 * @param \Titon\Event\Event $event
	 * @param int|int[] $id
	 * @param bool $cascade
	 * @return mixed
	 */
	public function preDelete(Event $event, $id, &$cascade) {
		$model = $this->getModel();

		foreach ($this->_counters as $alias => $counter) {
			foreach ((array) $id as $value) {
				$relation = $model->getRelation($alias);

				switch ($relation->getType()) {
					case Relation::MANY_TO_MANY:
						$results = $relation->getJunctionModel()
							->select()
							->where($relation->getForeignKey(), $value)
							->bindCallback($counter['scope'])
							->fetchAll(false);

						$this->setCache(['Junction', $alias, $value], $results);
					break;
					case Relation::MANY_TO_ONE:
						$this->setCache(['Record', $alias, $value], $model->read($value, false));
					break;
				}
			}
		}

		return true;
	}

	/**
	 * Sync counters after a record is deleted.
	 *
	 * @param \Titon\Event\Event $event
	 * @param int|int[] $id
	 */
	public function postDelete(Event $event, $id) {
		$this->syncCounters($id);
	}

	/**
	 * Sync counters after a record is saved.
	 *
	 * @param \Titon\Event\Event $event
	 * @param int|int[] $id
	 * @param bool $created
	 */
	public function postSave(Event $event, $id, $created = false) {
		$this->syncCounters($id);
	}

	/**
	 * Sync the count fields in the related model.
	 * Loop over each counter, and loop over each modified record ID.
	 * Determine the count while applying scope and update the relation record.
	 *
	 * @param int|int[] $ids
	 */
	public function syncCounters($ids) {
		foreach ($this->_counters as $alias => $counter) {
			$relation = $this->getModel()->getRelation($alias);

			switch ($relation->getType()) {
				case Relation::MANY_TO_MANY:
					$this->_syncManyToMany($relation, $ids, $counter);
				break;
				case Relation::MANY_TO_ONE:
					$this->_syncManyToOne($relation, $ids, $counter);
				break;
			}
		}

		// Reset cache for this sync
		$this->flushCache();
	}

	/**
	 * Sync many-to-many counters with the following process:
	 *
	 * 	- Loop through the current model IDs
	 * 	- Fetch all junction model records where foreign key matches current ID
	 * 	- Loop over junction records and grab the related foreign key value
	 * 	- Count all junction model records where related foreign key matches the previous related value
	 * 	- Update the related model with the junction count
	 *
	 * Using this example setup:
	 *
	 * 	- Entry (jfk:entry_id) has and belongs to many Tag (jfk:tag_id) (entry_count)
	 *
	 * @param \Titon\Model\Relation\ManyToMany $relation
	 * @param int|int[] $ids
	 * @param array $counter
	 */
	protected function _syncManyToMany(ManyToMany $relation, $ids, array $counter) {
		$alias = $relation->getAlias();
		$fk = $relation->getForeignKey();
		$rfk = $relation->getRelatedForeignKey();
		$junctionModel = $relation->getJunctionModel();
		$relatedModel = $relation->getRelatedModel();

		foreach ((array) $ids as $id) {
			$results = $this->getCache(['Junction', $alias, $id]);

			if (!$results) {
				$results = $junctionModel->select()
					->where($fk, $id)
					->bindCallback($counter['scope'])
					->fetchAll(false);
			}

			// Loop over each junction record and update the related record
			foreach ($results as $result) {
				$foreign_id = $result[$rfk];
				$cacheKey = [$alias, $fk, $foreign_id];

				// Skip if this has already been counted
				if ($this->hasCache($cacheKey)) {
					continue;
				}

				// Get a count of all junction records
				$count = $junctionModel->select()
					->where($rfk, $foreign_id)
					->count();

				// Update the related model's count field
				$relatedModel->update($foreign_id, [
					$counter['field'] => $count
				]);

				$this->setCache($cacheKey, true);
			}
		}
	}

	/**
	 * Sync many-to-one counters with the following process:
	 *
	 * 	- Loop through the current model IDs
	 * 	- Fetch the current model record that matches the ID
	 * 	- Count the current model records where foreign key matches the foreign key value from the previous record
	 * 	- Update the related model with the count
	 *
	 * Using this example setup:
	 *
	 * 	- Post (fk:topic_id) belongs to Topic (post_count)
	 *
	 * @param \Titon\Model\Relation\ManyToOne $relation
	 * @param int|int[] $ids
	 * @param array $counter
	 */
	protected function _syncManyToOne(ManyToOne $relation, $ids, array $counter) {
		$model = $this->getModel();
		$alias = $relation->getAlias();
		$fk = $relation->getForeignKey();
		$relatedModel = $relation->getRelatedModel();

		foreach ((array) $ids as $id) {
			$result = $this->getCache(['Record', $alias, $id]) ?: $model->read($id, false);
			$foreign_id = $result[$fk];
			$cacheKey = [$alias, $fk, $foreign_id];

			// Skip if this has already been counted
			if ($this->hasCache($cacheKey)) {
				continue;
			}

			// Get a count of all current records
			$count = $model->select()
				->where($fk, $foreign_id)
				->bindCallback($counter['scope'])
				->count();

			// Update the related model's count field
			$relatedModel->update($foreign_id, [
				$counter['field'] => $count
			]);

			$this->setCache($cacheKey, true);
		}
	}

}