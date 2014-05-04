<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Driver\Type;

/**
 * Represents a "VARCHAR" data type.
 *
 * @package Titon\Db\Driver\Type
 */
class StringType extends CharType {

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions() {
        return ['length' => 255] + parent::getDefaultOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return static::STRING;
    }

}