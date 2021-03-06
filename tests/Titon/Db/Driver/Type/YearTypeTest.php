<?php
namespace Titon\Db\Driver\Type;

use DateTime;
use PDO;
use Titon\Test\Stub\DriverStub;
use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Driver\Type\YearType $object
 */
class YearTypeTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new YearType(new DriverStub([]));
    }

    public function testFrom() {
        $this->assertSame(1988, $this->object->from('1988'));
        $this->assertSame(2011, $this->object->from('2011'));
        $this->assertSame(1985, $this->object->from('1985'));
        $this->assertSame(1995, $this->object->from('1995'));
        $this->assertSame(2013, $this->object->from('2013'));
    }

    public function testTo() {
        $this->assertSame('1988', $this->object->to(mktime(0, 2, 5, 2, 26, 1988)));
        $this->assertSame('2011', $this->object->to('2011-03-11 21:05:29'));
        $this->assertSame('1985', $this->object->to('June 6th 1985, 12:33pm'));
        $this->assertSame('1995', $this->object->to(new DateTime('1995-11-30 02:44:55')));
        $this->assertSame('2013', $this->object->to('Dec 24th 13, 02:15am'));
        $this->assertSame('1988', $this->object->to([
            'year' => 1988
        ]));
    }

    public function testGetName() {
        $this->assertEquals('year', $this->object->getName());
    }

    public function testGetBindingType() {
        $this->assertEquals(PDO::PARAM_STR, $this->object->getBindingType());
    }

    public function testGetDefaultOptions() {
        $this->assertEquals(['null' => true, 'default' => null, 'length' => 4], $this->object->getDefaultOptions());
    }

}