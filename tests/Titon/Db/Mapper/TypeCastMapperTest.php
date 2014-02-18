<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

use Titon\Db\Mapper\TypeCastMapper;
use Titon\Test\Stub\Repository\Stat;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Db\Mapper\TypeCastMapper.
 */
class TypeCastMapperTest extends TestCase {

    /**
     * Test that fields are type cast.
     */
    public function testAfter() {
        $mapper = new TypeCastMapper();
        $mapper->setRepository(new Stat());

        $data = [
            'name' => 'foo',
            'health' => '123',
            'energy' => 456,
            'damage' => '789.15',
            'defense' => '12.45',
            'range' => '22.25',
            'isMelee' => '0',
        ];

        $this->assertSame([[
            'name' => 'foo',
            'health' => 123,
            'energy' => 456,
            'damage' => 789.15,
            'defense' => 12.45,
            'range' => 22.25,
            'isMelee' => false
        ]], $mapper->after([$data]));
    }

}