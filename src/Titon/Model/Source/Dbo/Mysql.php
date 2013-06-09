<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Source\Dbo;

use Titon\Model\Source\Dbo\AbstractDboSource;
use \PDO;

/**
 * Represents the MySQL database.
 */
class Mysql extends AbstractDboSource {

	/**
	 * Configuration.
	 *
	 * @type array {
	 *		@type int $port	Default port
	 * ]
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
	 * {@inheritdoc}
	 */
	public function getDriver() {
		return 'mysql';
	}

}