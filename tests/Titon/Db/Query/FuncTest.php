<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Query;

use Titon\Common\Config;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Db\Query\Func.
 *
 * @property \Titon\Db\Query\Func $object
 */
class FuncTest extends TestCase {

    /**
     * Test data is persisted.
     */
    public function testFunction() {
        $func = new Func('SUBSTRING', ['TitonFramework', 5]);

        $this->assertEquals('SUBSTRING', $func->getName());
        $this->assertEquals(', ', $func->getSeparator());
        $this->assertEquals([
            ['type' => null, 'value' => 'TitonFramework'],
            ['type' => null, 'value' => 5],
        ], $func->getArguments());

        $func = new Func('SUBSTRING', ['Titon', 'FROM -4 FOR 2' => Func::LITERAL], ' ');

        $this->assertEquals('SUBSTRING', $func->getName());
        $this->assertEquals(' ', $func->getSeparator());
        $this->assertEquals([
            ['type' => null, 'value' => 'Titon'],
            ['type' => Func::LITERAL, 'value' => 'FROM -4 FOR 2'],
        ], $func->getArguments());

        $func1 = new Func('CHAR', ['0x65 USING utf8' => Func::LITERAL]);
        $func2 = new Func('CHARSET', $func1);

        $this->assertEquals('CHARSET', $func2->getName());
        $this->assertEquals(', ', $func2->getSeparator());
        $this->assertEquals([
            ['type' => null, 'value' => $func1],
        ], $func2->getArguments());
    }

}