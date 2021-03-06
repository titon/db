<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Behavior;

use Titon\Db\Query;
use Titon\Db\Exception\InvalidArgumentException;
use Titon\Event\Event;
use Titon\Utility\Sanitize;

/**
 * The FilterBehavior will run sanitization filters on specific fields during an insert or update.
 * The currently supported filters are: html stripping, html escaping, newline cleaning, whitespace cleaning and xss filtering.
 *
 * @package Titon\Db\Behavior
 */
class FilterBehavior extends AbstractBehavior {

    const HTML = 'html';
    const NEWLINES = 'newlines';
    const WHITESPACE = 'whitespace';
    const XSS = 'xss';

    /**
     * Mapped filters per field.
     *
     * @type array
     */
    protected $_filters = [];

    /**
     * Define a filter for a specific field.
     *
     * @param string $field
     * @param string $filter
     * @param array $options
     * @return $this
     * @throws \Titon\Db\Exception\InvalidArgumentException
     */
    public function filter($field, $filter, array $options = []) {
        if (!in_array($filter, [self::HTML, self::NEWLINES, self::WHITESPACE, self::XSS])) {
            throw new InvalidArgumentException(sprintf('Filter %s does not exist', $filter));
        }

        $this->_filters[$field][$filter] = $options;

        return $this;
    }

    /**
     * Return the filter configurations.
     *
     * @return array
     */
    public function getFilters() {
        return $this->_filters;
    }

    /**
     * Run the filters before each save.
     *
     * @param \Titon\Event\Event $event
     * @param \Titon\Db\Query $query
     * @param int|int[] $id
     * @param array $data
     * @return bool
     */
    public function preSave(Event $event, Query $query, $id, array &$data) {
        $filters = $this->getFilters();

        foreach ($data as $key => $value) {
            if (empty($filters[$key])) {
                continue;
            }

            $filter = $filters[$key];

            // HTML escape
            if (isset($filter['html'])) {
                $value = Sanitize::html($value, $filter['html']);
            }

            // Newlines
            if (isset($filter['newlines'])) {
                $value = Sanitize::newlines($value, $filter['newlines']);
            }

            // Whitespace
            if (isset($filter['whitespace'])) {
                $value = Sanitize::whitespace($value, $filter['whitespace']);
            }

            // XSS
            if (isset($filter['xss'])) {
                $value = Sanitize::xss($value, $filter['xss']);
            }

            $data[$key] = $value;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function registerEvents() {
        return [
            'db.preSave' => 'preSave'
        ];
    }

}