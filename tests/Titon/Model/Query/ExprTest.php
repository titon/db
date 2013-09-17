<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Model\Query;

use Titon\Test\TestCase;

/**
 * Test class for Titon\Model\Query\Expr.
 *
 * @property \Titon\Model\Query\Expr $object
 */
class ExprTest extends TestCase {

    /**
     * Test data is persisted.
     */
    public function testExpression() {
        $expr = new Expr('column', '+', 5);

        $this->assertEquals('column', $expr->getField());
        $this->assertEquals('+', $expr->getOperator());
        $this->assertEquals('5', $expr->getValue());
    }

}