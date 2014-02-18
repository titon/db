<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Mapper;

use Titon\Common\Base;
use Titon\Db\Mapper;
use Titon\Db\Traits\RepositoryAware;

/**
 * Inherit repository management functionality for mappers.
 *
 * @package Titon\Db\Mapper
 */
abstract class AbstractMapper extends Base implements Mapper {
    use RepositoryAware;

    /**
     * {@inheritdoc}
     */
    public function after(array $results) {
        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function before(array $data) {
        return $data;
    }

}