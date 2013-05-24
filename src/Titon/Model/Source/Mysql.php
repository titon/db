<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Source;

use Titon\Model\Source\AbstractDboSource;
use \PDO;

/**
 * Represents the MySQL database.
 */
class Mysql extends AbstractDboSource {

	/**
	 * Configuration.
	 *
	 * @type array
	 */
	protected $_config = [
		'port' => 3306
	];

	/**
	 * Default MySQL flags.
	 *
	 * @type array
	 */
	protected $_flags = [
		PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
	];

	/**
	 * Return the MySQL PDO driver name.
	 *
	 * @return string
	 */
	public function getDriver() {
		return 'mysql';
	}

}