<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Relation;

use Titon\Db\Relation;

/**
 * Represents a one-to-many table relationship.
 * Also known as a has many.
 *
 * @link http://en.wikipedia.org/wiki/Cardinality_%28data_modeling%29
 *
 * @package Titon\Db\Relation
 */
class OneToMany extends AbstractRelation {

    /**
     * {@inheritdoc}
     */
    public function getType() {
        return Relation::ONE_TO_MANY;
    }

}