<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

use Titon\Test\Stub\DriverStub;

error_reporting(E_ALL | E_STRICT);

define('TEST_DIR', __DIR__);
define('TEMP_DIR', __DIR__ . '/tmp');
define('VENDOR_DIR', dirname(TEST_DIR) . '/vendor');

if (!file_exists(VENDOR_DIR . '/autoload.php')) {
	exit('Please install Composer in the root folder before running tests!');
}

$loader = require VENDOR_DIR . '/autoload.php';
$loader->add('Titon\\Model', TEST_DIR);

// Define database credentials
$db = [
	'database' => 'test',
	'host' => 'localhost',
	'user' => 'root',
	'pass' => ''
];

Titon\Common\Config::set('db', $db);

Titon\Common\Registry::factory('Titon\Model\Connection')->addDriver(new DriverStub('default', $db));