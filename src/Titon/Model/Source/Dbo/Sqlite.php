<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Source\Dbo;

use Titon\Model\Source\Dbo\AbstractDboSource;

/**
 * Represents the SQLite database.
 */
class Sqlite extends AbstractDboSource {

	/**
	 * {@inheritdoc}
	 */
	public function getDriver() {
		return 'sqlite';
	}

}