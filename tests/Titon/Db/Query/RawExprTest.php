<?php
namespace Titon\Db\Query;

use Titon\Test\TestCase;

class RawExprTest extends TestCase {

    public function testExpression() {
        $expr = new RawExpr('`column` = 5');

        $this->assertEquals('`column` = 5', $expr->getValue());
        $this->assertEquals('`column` = 5', (string) $expr);
    }

}