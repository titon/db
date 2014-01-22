<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Query;

use Exception;
use Titon\Test\Stub\Repository\User;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Db\Query\SubQuery.
 *
 * @property \Titon\Db\Query\SubQuery $object
 */
class SubQueryTest extends TestCase {

    /**
     * Test alias is set.
     */
    public function testAsAlias() {
        $subQuery = new SubQuery(SubQuery::SELECT, new User());
        $this->assertEquals(null, $subQuery->getAlias());

        $subQuery->asAlias('column');
        $this->assertEquals('column', $subQuery->getAlias());
    }

    /**
     * Test filter is set.
     */
    public function testWithFilter() {
        $subQuery = new SubQuery(SubQuery::SELECT, new User());
        $this->assertEquals(null, $subQuery->getFilter());

        $subQuery->withFilter(SubQuery::ALL);
        $this->assertEquals('all', $subQuery->getFilter());

        try {
            $subQuery->withFilter('foobar');
            $this->assertTrue(false);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

}