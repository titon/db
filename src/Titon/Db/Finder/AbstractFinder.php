<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Finder;

use Titon\Common\Base;
use Titon\Db\Finder;
use Titon\Db\Query;

/**
 * Implement basic finder functionality.
 *
 * @package Titon\Db\Finder
 * @codeCoverageIgnore
 */
abstract class AbstractFinder extends Base implements Finder {

    /**
     * {@inheritdoc}
     */
    public function after(array $results, array $options = []) {
        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function before(Query $query, array $options = []) {
        return $query;
    }

}