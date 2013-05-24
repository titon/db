<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model;

use Titon\Model\Exception;
use Titon\Model\Source;

/**
 * Manages data source connection and login credentials.
 */
class Connection {

	/**
	 * Source mappings.
	 *
	 * @type \Titon\Model\Source[]
	 */
	protected $_sources = [];

	/**
	 * Add a data source that houses login credentials.
	 *
	 * @param \Titon\Model\Source $source
	 * @return \Titon\Model\Connection
	 */
	public function addSource(Source $source) {
		$this->_sources[$source->getKey()] = $source;

		return $this;
	}

	/**
	 * Return a source by key.
	 *
	 * @param string $key
	 * @return \Titon\Model\Source
	 * @throws \Titon\Model\Exception
	 */
	public function getSource($key) {
		if (isset($this->_sources[$key])) {
			return $this->_sources[$key];
		}

		throw new Exception(sprintf('Invalid data source %s', $key));
	}

	/**
	 * Returns the list of sources.
	 *
	 * @return \Titon\Model\Source[]
	 */
	public function getSources() {
		return $this->_sources;
	}

}