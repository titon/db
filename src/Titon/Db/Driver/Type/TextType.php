<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Type;

use \PDO;

/**
 * Represents a "TEXT" data type.
 *
 * @package Titon\Db\Driver\Type
 */
class TextType extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function getBindingType() {
        return PDO::PARAM_STR;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return self::TEXT;
    }

}