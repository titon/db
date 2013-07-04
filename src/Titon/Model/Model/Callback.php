<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Model;

use Titon\Model\Query;

/**
 * Provides a set of callbacks that models and behaviors should implement.
 *
 * @package Titon\Model\Model
 */
interface Callback {

	/**
	 * Callback called before a delete query.
	 * Modify cascading by overwriting the value.
	 * Return a falsey value to stop the process.
	 *
	 * @param int|int[] $id
	 * @param bool $cascade
	 * @return mixed
	 */
	public function preDelete($id, &$cascade);

	/**
	 * Callback called before a select query.
	 * Return an array of data to use instead of the fetch results.
	 *
	 * @param \Titon\Model\Query $query
	 * @param string $fetchType
	 * @return mixed
	 */
	public function preFetch(Query $query, $fetchType);

	/**
	 * Callback called before an insert or update query.
	 * Return a falsey value to stop the process.
	 *
	 * @param int|int[] $id
	 * @param array $data
	 * @return mixed
	 */
	public function preSave($id, array $data);

	/**
	 * Callback called after a delete query.
	 *
	 * @param int|int[] $id
	 */
	public function postDelete($id);

	/**
	 * Callback called after a select query.
	 *
	 * @param array $results The results of the query
	 * @param string $fetchType Type of fetch used
	 * @return array
	 */
	public function postFetch(array $results, $fetchType);

	/**
	 * Callback called after an insert or update query.
	 *
	 * @param int|int[] $id
	 * @param bool $created If the record was created
	 */
	public function postSave($id, $created = false);

}