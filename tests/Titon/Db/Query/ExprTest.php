<?php
namespace Titon\Db\Query;

use Titon\Test\TestCase;

class ExprTest extends TestCase {

    public function testExpression() {
        $expr = new Expr('column', '+', 5);

        $this->assertEquals('column', $expr->getField());
        $this->assertEquals('+', $expr->getOperator());
        $this->assertEquals('5', $expr->getValue());
    }

}