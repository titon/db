<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Behavior;

use Titon\Common\Base;
use Titon\Event\Listener;
use Titon\Db\Behavior;
use Titon\Db\Query;
use Titon\Db\RepositoryAware;

/**
 * Provides shared functionality for behaviors.
 *
 * @package Titon\Db\Behavior
 * @codeCoverageIgnore
 */
abstract class AbstractBehavior extends Base implements Behavior, Listener {
    use RepositoryAware;

    /**
     * {@inheritdoc}
     */
    public function getAlias() {
        return str_replace('Behavior', '', $this->inform('shortClassName'));
    }

}