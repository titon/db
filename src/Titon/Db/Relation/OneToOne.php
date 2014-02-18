<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Relation;

use Titon\Db\Relation;

/**
 * Represents a one-to-one table relationship.
 * Also known as a has one.
 *
 * @link http://en.wikipedia.org/wiki/Cardinality_%28data_modeling%29
 *
 * @package Titon\Db\Relation
 */
class OneToOne extends AbstractRelation {

    /**
     * {@inheritdoc}
     */
    public function getType() {
        return Relation::ONE_TO_ONE;
    }

}