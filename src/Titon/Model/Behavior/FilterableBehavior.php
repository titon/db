<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Model\Behavior;

use Titon\Event\Event;
use Titon\Model\Exception\InvalidArgumentException;
use Titon\Utility\Sanitize;

/**
 * The FilterableBehavior will run sanitization filters on specific fields during an insert or update.
 * The currently supported filters are: html stripping, html escaping, newline cleaning, whitespace cleaning and xss filtering.
 *
 * @package Titon\Model\Behavior
 */
class FilterableBehavior extends AbstractBehavior {

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
     * @return \Titon\Model\Behavior\FilterableBehavior
     * @throws \Titon\Model\Exception\InvalidArgumentException
     */
    public function filter($field, $filter, array $options = []) {
        if (!in_array($filter, [self::HTML, self::NEWLINES, self::WHITESPACE, self::XSS])) {
            throw new InvalidArgumentException(sprintf('Filter %s does not exist', $filter));
        }

        $this->_filters[$field][$filter] = $options;

        return $this;
    }

    /**
     * Run the filters before each save.
     *
     * @param \Titon\Event\Event $event
     * @param int|int[] $id
     * @param array $data
     * @return bool
     */
    public function preSave(Event $event, $id, array &$data) {
        foreach ($data as $key => $value) {
            if (empty($this->_filters[$key])) {
                continue;
            }

            $filter = $this->_filters[$key];

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

}