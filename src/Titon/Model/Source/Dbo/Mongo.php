<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Source\Dbo;

use Titon\Model\Source\Dbo\AbstractDboSource;

/**
 * Represents the MongoDB database.
 */
class Mongo extends AbstractDboSource {

	/**
	 * {@inheritdoc}
	 */
	public function getDriver() {
		return 'mongodb';
	}

}