<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Query;

use Titon\Db\Query\Predicate;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Db\Query\Predicate.
 *
 * @property \Titon\Db\Query\Predicate $object
 */
class PredicateTest extends TestCase {

    /**
     * This method is called before a test is executed.
     */
    protected function setUp() {
        parent::setUp();

        $this->object = new Predicate(Predicate::ALSO);
    }

    /**
     * Test adding params.
     */
    public function testAdd() {
        $this->object->add('id', '=', 1);
        $expected = ['id=1' => new Expr('id', '=', 1)];
        $this->assertEquals($expected, $this->object->getParams());

        $this->object->add('id', '=', 1); // no dupes
        $this->object->add('name', 'like', 'Titon');
        $expected['namelikeTiton'] = new Expr('name', 'like', 'Titon');
        $this->assertEquals($expected, $this->object->getParams());
    }

    /**
     * Test AND sub-grouping.
     */
    public function testAlso() {
        $also = new Predicate(Predicate::ALSO);
        $also->in('id', [1, 2, 3]);

        $this->object->also(function() {
            $this->in('id', [1, 2, 3]);
        });
        $this->assertEquals([$also], $this->object->getParams());
    }

    /**
     * Test between clause.
     */
    public function testBetween() {
        $this->object->between('size', 100, 500);
        $this->assertEquals([
            'sizebetween[100,500]' => new Expr('size', 'between', [100, 500])
        ], $this->object->getParams());
    }

    /**
     * Test OR sub-grouping.
     */
    public function testEither() {
        $either = new Predicate(Predicate::EITHER);
        $either->notEq('level', 1)->notEq('level', 2);

        $this->object->either(function() {
            $this->notEq('level', 1)->notEq('level', 2);
        });
        $this->assertEquals([$either], $this->object->getParams());
    }

    /**
     * Test equals clause and all its variants.
     */
    public function testEq() {
        $this->object->eq('id', 1);
        $expected = [
            'id=1' => new Expr('id', '=', 1)
        ];

        $this->assertEquals($expected, $this->object->getParams());

        $this->object->eq('category', [5, 10, 15]);
        $expected['categoryin[5,10,15]'] = new Expr('category', 'in', [5, 10, 15]);

        $this->assertEquals($expected, $this->object->getParams());

        $this->object->eq('name', null);
        $expected['nameisNull'] = new Expr('name', 'isNull', null);

        $this->assertEquals($expected, $this->object->getParams());
    }

    /**
     * Test adding params.
     */
    public function testExpr() {
        $this->object->expr('id', '!=', 1);
        $expected = ['id!=1' => new Expr('id', '!=', 1)];
        $this->assertEquals($expected, $this->object->getParams());
    }

    /**
     * Test greater than or equal clause.
     */
    public function testGte() {
        $this->object->gte('size', 250);
        $this->assertEquals([
            'size>=250' => new Expr('size', '>=', 250)
        ], $this->object->getParams());
    }

    /**
     * Test greater than clause.
     */
    public function testGt() {
        $this->object->gt('size', 666);
        $this->assertEquals([
            'size>666' => new Expr('size', '>', 666)
        ], $this->object->getParams());
    }

    /**
     * Test in clause.
     */
    public function testIn() {
        $this->object->in('color', ['red', 'green', 'blue']);
        $this->assertEquals([
            'colorin["red","green","blue"]' => new Expr('color', 'in', ['red', 'green', 'blue'])
        ], $this->object->getParams());
    }

    /**
     * Test like clause.
     */
    public function testLike() {
        $this->object->like('name', 'Titon%');
        $this->object->like('name', '%Titon%');
        $this->assertEquals([
            'namelikeTiton%' => new Expr('name', 'like', 'Titon%'),
            'namelike%Titon%' => new Expr('name', 'like', '%Titon%')
        ], $this->object->getParams());
    }

    /**
     * Test less than or equal clause.
     */
    public function testLte() {
        $this->object->lte('size', 1337);
        $this->assertEquals([
            'size<=1337' => new Expr('size', '<=', 1337)
        ], $this->object->getParams());
    }

    /**
     * Test less than clause.
     */
    public function testLt() {
        $this->object->lt('size', 1234);
        $this->assertEquals([
            'size<1234' => new Expr('size', '<', 1234)
        ], $this->object->getParams());
    }

    /**
     * Test XOR sub-grouping.
     */
    public function testMaybe() {
        $maybe = new Predicate(Predicate::MAYBE);
        $maybe->notIn('color', ['red', 'green'])->notIn('size', ['large', 'small']);

        $this->object->maybe(function() {
            $this->notIn('color', ['red', 'green'])->notIn('size', ['large', 'small']);
        });
        $this->assertEquals([$maybe], $this->object->getParams());
    }

    /**
     * Test NOR sub-grouping.
     */
    public function testNeither() {
        $neither = new Predicate(Predicate::NEITHER);
        $neither->notIn('color', ['red', 'green'])->notIn('size', ['large', 'small']);

        $this->object->neither(function() {
            $this->notIn('color', ['red', 'green'])->notIn('size', ['large', 'small']);
        });
        $this->assertEquals([$neither], $this->object->getParams());
    }

    /**
     * Test not between clause.
     */
    public function testNotBetween() {
        $this->object->notBetween('size', 123, 124);
        $this->assertEquals([
            'sizenotBetween[123,124]' => new Expr('size', 'notBetween', [123, 124])
        ], $this->object->getParams());
    }

    /**
     * Test not equals clause and all its variants.
     */
    public function testNotEq() {
        $this->object->notEq('id', 1);
        $expected = [
            'id!=1' => new Expr('id', '!=', 1)
        ];

        $this->assertEquals($expected, $this->object->getParams());

        $this->object->notEq('category', [5, 10, 15]);
        $expected['categorynotIn[5,10,15]'] = new Expr('category', 'notIn', [5, 10, 15]);

        $this->assertEquals($expected, $this->object->getParams());

        $this->object->notEq('name', null);
        $expected['nameisNotNull'] = new Expr('name', 'isNotNull', null);

        $this->assertEquals($expected, $this->object->getParams());
    }

    /**
     * Test not in clause.
     */
    public function testNotIn() {
        $this->object->notIn('color', ['red', 'green', 'blue']);
        $this->assertEquals([
            'colornotIn["red","green","blue"]' => new Expr('color', 'notIn', ['red', 'green', 'blue'])
        ], $this->object->getParams());
    }

    /**
     * Test not like clause.
     */
    public function testNotLike() {
        $this->object->notLike('name', 'Titon%');
        $this->object->notLike('name', '%Titon%');
        $this->assertEquals([
            'namenotLikeTiton%' => new Expr('name', 'notLike', 'Titon%'),
            'namenotLike%Titon%' => new Expr('name', 'notLike', '%Titon%')
        ], $this->object->getParams());
    }

    /**
     * Test not null clause.
     */
    public function testNotNull() {
        $this->object->notNull('title');
        $this->assertEquals([
            'titleisNotNull' => new Expr('title', 'isNotNull', null)
        ], $this->object->getParams());
    }

    /**
     * Test null clause.
     */
    public function testNull() {
        $this->object->null('title');
        $this->assertEquals([
            'titleisNull' => new Expr('title', 'isNull', null)
        ], $this->object->getParams());
    }

}