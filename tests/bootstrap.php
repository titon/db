<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

error_reporting(E_ALL | E_STRICT);

define('TEST_DIR', __DIR__);
define('TEMP_DIR', __DIR__ . '/tmp');
define('VENDOR_DIR', dirname(TEST_DIR) . '/vendor');

if (!file_exists(VENDOR_DIR . '/autoload.php')) {
    exit('Please install Composer in the root folder before running tests!');
}

$loader = require VENDOR_DIR . '/autoload.php';
$loader->add('Titon\\Db', TEST_DIR);

// Define database credentials
$db = [
    'database' => 'titon_test',
    'host' => '127.0.0.1',
    'user' => 'root',
    'pass' => ''
];

Titon\Common\Config::set('db', $db);

// Used by tables
Titon\Db\Connection::registry()
    ->addDriver('default', new Titon\Test\Stub\DriverStub($db));