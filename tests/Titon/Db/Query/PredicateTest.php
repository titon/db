<?php
namespace Titon\Db\Query;

use Titon\Test\TestCase;

/**
 * @property \Titon\Db\Query\Predicate $object
 */
class PredicateTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $this->object = new Predicate(Predicate::ALSO);
    }

    public function testAdd() {
        $this->object->add('id', '=', 1);
        $expected = [new Expr('id', '=', 1)];
        $this->assertEquals($expected, $this->object->getParams());

        $this->object->add('name', 'like', 'Titon');
        $expected[] = new Expr('name', 'like', 'Titon');
        $this->assertEquals($expected, $this->object->getParams());
    }

    public function testAddFunction() {
        $func = new Func('SUBSTRING', ['Titon', 3]);
        $this->object->add($func, '=', 'on');

        $this->assertEquals([new Expr($func, '=', 'on')], $this->object->getParams());
    }

    public function testAddDateTime() {
        $date = new \DateTime();
        $this->object->add('created', '>', $date);

        $this->assertEquals([new Expr('created', '>', $date)], $this->object->getParams());
    }

    public function testAlso() {
        $also = new Predicate(Predicate::ALSO);
        $also->in('id', [1, 2, 3]);

        $this->object->also(function(Predicate $predicate) {
            $predicate->in('id', [1, 2, 3]);
        });
        $this->assertEquals([$also], $this->object->getParams());
    }

    public function testBetween() {
        $this->object->between('size', 100, 500);
        $this->assertEquals([new Expr('size', 'between', [100, 500])], $this->object->getParams());
    }

    public function testEither() {
        $either = new Predicate(Predicate::EITHER);
        $either->notEq('level', 1)->notEq('level', 2);

        $this->object->either(function(Predicate $predicate) {
            $predicate->notEq('level', 1)->notEq('level', 2);
        });
        $this->assertEquals([$either], $this->object->getParams());
    }

    public function testEq() {
        $this->object->eq('id', 1);
        $expected = [new Expr('id', '=', 1)];
        $this->assertEquals($expected, $this->object->getParams());

        $this->object->eq('category', [5, 10, 15]);
        $expected[] = new Expr('category', 'in', [5, 10, 15]);
        $this->assertEquals($expected, $this->object->getParams());

        $this->object->eq('name', null);
        $expected[] = new Expr('name', 'isNull', null);
        $this->assertEquals($expected, $this->object->getParams());
    }

    public function testExpr() {
        $this->object->expr('id', '!=', 1);
        $expected = [new Expr('id', '!=', 1)];
        $this->assertEquals($expected, $this->object->getParams());
    }

    public function testGetType() {
        $this->assertEquals(Predicate::ALSO, $this->object->getType());

        $pred = new Predicate(Predicate::MAYBE);
        $this->assertEquals(Predicate::MAYBE, $pred->getType());
    }

    public function testGte() {
        $this->object->gte('size', 250);
        $this->assertEquals([new Expr('size', '>=', 250)], $this->object->getParams());
    }

    public function testGt() {
        $this->object->gt('size', 666);
        $this->assertEquals([new Expr('size', '>', 666)], $this->object->getParams());
    }

    public function testHasParam() {
        $this->assertFalse($this->object->hasParam('created'));
        $this->object->gte('created', time());
        $this->assertTrue($this->object->hasParam('created'));
    }

    public function testIn() {
        $this->object->in('color', ['red', 'green', 'blue']);
        $this->assertEquals([new Expr('color', 'in', ['red', 'green', 'blue'])], $this->object->getParams());
    }

    public function testLike() {
        $this->object->like('name', 'Titon%');
        $this->object->like('name', '%Titon%');
        $this->assertEquals([
            new Expr('name', 'like', 'Titon%'),
            new Expr('name', 'like', '%Titon%')
        ], $this->object->getParams());
    }

    public function testLte() {
        $this->object->lte('size', 1337);
        $this->assertEquals([new Expr('size', '<=', 1337)], $this->object->getParams());
    }

    public function testLt() {
        $this->object->lt('size', 1234);
        $this->assertEquals([new Expr('size', '<', 1234)], $this->object->getParams());
    }

    public function testMaybe() {
        $maybe = new Predicate(Predicate::MAYBE);
        $maybe->notIn('color', ['red', 'green'])->notIn('size', ['large', 'small']);

        $this->object->maybe(function(Predicate $predicate) {
            $predicate->notIn('color', ['red', 'green'])->notIn('size', ['large', 'small']);
        });
        $this->assertEquals([$maybe], $this->object->getParams());
    }

    public function testNeither() {
        $neither = new Predicate(Predicate::NEITHER);
        $neither->notIn('color', ['red', 'green'])->notIn('size', ['large', 'small']);

        $this->object->neither(function(Predicate $predicate) {
            $predicate->notIn('color', ['red', 'green'])->notIn('size', ['large', 'small']);
        });
        $this->assertEquals([$neither], $this->object->getParams());
    }

    public function testNotBetween() {
        $this->object->notBetween('size', 123, 124);
        $this->assertEquals([new Expr('size', 'notBetween', [123, 124])], $this->object->getParams());
    }

    public function testNotEq() {
        $this->object->notEq('id', 1);
        $expected = [new Expr('id', '!=', 1)];
        $this->assertEquals($expected, $this->object->getParams());

        $this->object->notEq('category', [5, 10, 15]);
        $expected[] = new Expr('category', 'notIn', [5, 10, 15]);
        $this->assertEquals($expected, $this->object->getParams());

        $this->object->notEq('name', null);
        $expected[] = new Expr('name', 'isNotNull', null);
        $this->assertEquals($expected, $this->object->getParams());
    }

    public function testNotIn() {
        $this->object->notIn('color', ['red', 'green', 'blue']);
        $this->assertEquals([new Expr('color', 'notIn', ['red', 'green', 'blue'])], $this->object->getParams());
    }

    public function testNotLike() {
        $this->object->notLike('name', 'Titon%');
        $this->object->notLike('name', '%Titon%');
        $this->assertEquals([
            new Expr('name', 'notLike', 'Titon%'),
            new Expr('name', 'notLike', '%Titon%')
        ], $this->object->getParams());
    }

    public function testNotNull() {
        $this->object->notNull('title');
        $this->assertEquals([new Expr('title', 'isNotNull', null)], $this->object->getParams());
    }

    public function testNull() {
        $this->object->null('title');
        $this->assertEquals([new Expr('title', 'isNull', null)], $this->object->getParams());
    }

}