<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Model\Behavior;

use Titon\Event\Event;
use Titon\Model\Exception\InvalidArgumentException;
use Titon\Utility\Hash;

/**
 * The ConvertableBehavior will convert a field into a specific type before an insert or update,
 * and then convert the field back to its original type when the record is retrieved.
 *
 * @package Titon\Model\Behavior
 */
class ConvertableBehavior extends AbstractBehavior {

    /**
     * Mapped converters per field.
     *
     * @type array
     */
    protected $_converters = [];

    /**
     * Default converter settings.
     *
     * @type array
     */
    protected $_defaults = [
        'serialize' => [],
        'json' => [
            'object' => false
        ],
        'html' => [
            'decode' => false,
            'encoding' => 'UTF-8',
            'flags' => ENT_QUOTES
        ],
        'base64' => [],
        'custom' => []
    ];

    /**
     * Define a converter for a specific field.
     *
     * @param string $field
     * @param string $type
     * @param array $options
     * @return \Titon\Model\Behavior\ConvertableBehavior
     * @throws \Titon\Model\Exception\InvalidArgumentException
     */
    public function convert($field, $type, array $options) {
        if (empty($this->_defaults[$type])) {
            throw new InvalidArgumentException(sprintf('Converter %s does not exist', $type));
        }

        $this->_converters[$field] = Hash::merge([
            'encode' => true,
            'decode' => true,
        ], $this->_defaults[$type], $options);
    }

    /**
     * Apply the encoding converter before a record is saved.
     *
     * @param \Titon\Event\Event $event
     * @param int|int[] $id
     * @param array $data
     * @return bool
     */
    public function preSave(Event $event, $id, array &$data) {
        $model = $this->getModel();

        foreach ($data as $key => $value) {
            if (empty($this->_converters[$key])) {
                continue;
            }

            $converter = $this->_converters[$key];

            // Exit if encoding should not happen
            if (!$converter['encode']) {
                continue;
            }

            switch ($converter['type']) {
                case 'serialize':   $value = $this->toSerialize($value, $converter); break;
                case 'json':        $value = $this->toJson($value, $converter); break;
                case 'html':        $value = $this->toHtml($value, $converter); break;
                case 'base64':      $value = $this->toBase64($value, $converter); break;
                case 'custom':
                    if (method_exists($model, $converter['encode'])) {
                        $value = call_user_func_array([$model, $converter['encode']], [$value, $converter]);
                    }
                break;
            }

            $data[$key] = $value;
        }

        return true;
    }

    /**
     * Apply the decoding converter after a record is retrieved.
     *
     * @param \Titon\Event\Event $event
     * @param array $results
     * @param string $fetchType
     */
    public function postFetch(Event $event, array &$results, $fetchType) {
        if ($fetchType !== 'fetchAll') {
            return;
        }

        $model = $this->getModel();

        foreach ($results as $key => $value) {
            if (empty($this->_converters[$key])) {
                continue;
            }

            $converter = $this->_converters[$key];

            // Exit if decoding should not happen
            if (!$converter['decode']) {
                continue;
            }

            switch ($converter['type']) {
                case 'serialize':   $value = $this->fromSerialize($value, $converter); break;
                case 'json':        $value = $this->fromJson($value, $converter); break;
                case 'html':        $value = $this->fromHtml($value, $converter); break;
                case 'base64':      $value = $this->fromBase64($value, $converter); break;
                case 'custom':
                    if (method_exists($model, $converter['decode'])) {
                        $value = call_user_func_array([$model, $converter['decode']], [$value, $converter]);
                    }
                break;
            }

            $results[$key] = $value;
        }
    }

    /**
     * Convert a serialized string into an array.
     *
     * @param string $value
     * @param array $options
     * @return array
     */
    public function fromSerialize($value, array $options) {
        return @unserialize($value);
    }

    /**
     * Convert a string into JSON.
     *
     * @param string $value
     * @param array $options
     * @return array
     */
    public function fromJson($value, array $options) {
        return json_decode($value, !$options['object']);
    }

    /**
     * Decode HTML entities within the string.
     *
     * @param string $value
     * @param array $options
     * @return string
     */
    public function fromHtml($value, array $options) {
        return html_entity_decode($value, $options['flags'], $options['encoding']);
    }

    /**
     * Decode the string from base64.
     *
     * @param string $value
     * @param array $options
     * @return string
     */
    public function fromBase64($value, array $options) {
        return base64_decode($value);
    }

    /**
     * Serialize a value.
     *
     * @param mixed $value
     * @param array $options
     * @return string
     */
    public function toSerialize($value, array $options) {
        return serialize($value);
    }

    /**
     * Encode a value to a JSON string.
     *
     * @param mixed $value
     * @param array $options
     * @return string
     */
    public function toJson($value, array $options) {
        return json_encode($value);
    }

    /**
     * Escape HTML entities within a string.
     *
     * @param mixed $value
     * @param array $options
     * @return string
     */
    public function toHtml($value, array $options) {
        return htmlentities($value, $options['flags'], $options['encoding']);
    }

    /**
     * Encode a value with base64.
     *
     * @param mixed $value
     * @param array $options
     * @return string
     */
    public function toBase64($value, array $options) {
        return base64_encode($value);
    }

}