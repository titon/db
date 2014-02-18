<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Mapper;

use Titon\Db\Driver\Type\AbstractType;

/**
 * Loop over the results and cast column types dependent on the current driver.
 *
 * @package Titon\Db\Mapper
 */
class TypeCastMapper extends AbstractMapper {

    /**
     * {@inheritdoc}
     */
    public function after(array $results) {
        $schema = $this->getRepository()->getSchema()->getColumns();

        if (!$schema) {
            return $results;
        }

        $driver = $this->getRepository()->getDriver();

        foreach ($results as $i => $result) {
            foreach ($result as $field => $value) {
                if (isset($schema[$field])) {
                    $results[$i][$field] = AbstractType::factory($schema[$field]['type'], $driver)->from($value);
                }
            }
        }

        return $results;
    }

}