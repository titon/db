<?php
/**
 * @copyright   2010-2013, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Behavior;

use Titon\Event\Event;
use Titon\Db\Exception\InvalidArgumentException;
use Titon\Utility\Hash;

/**
 * The ConverterBehavior will convert a field into a specific type before an insert or update,
 * and then convert the field back to its original type when the record is retrieved.
 *
 * @package Titon\Db\Behavior
 */
class ConverterBehavior extends AbstractBehavior {

    const SERIALIZE = 'serialize';
    const JSON = 'json';
    const BASE64 = 'base64';
    const CUSTOM = 'custom';

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
        self::SERIALIZE => [],
        self::JSON => ['object' => false],
        self::BASE64 => [],
        self::CUSTOM => []
    ];

    /**
     * Define a converter for a specific field.
     *
     * @param string $field
     * @param string $type
     * @param array $options
     * @return \Titon\Db\Behavior\ConverterBehavior
     * @throws \Titon\Db\Exception\InvalidArgumentException
     */
    public function convert($field, $type, array $options) {
        if (!isset($this->_defaults[$type])) {
            throw new InvalidArgumentException(sprintf('Converter %s does not exist', $type));
        }

        $this->_converters[$field] = Hash::merge([
            'encode' => true,
            'decode' => true,
            'type' => $type,
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
        $repo = $this->getRepository();

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
                case self::SERIALIZE:   $value = $this->toSerialize($value, $converter); break;
                case self::JSON:        $value = $this->toJson($value, $converter); break;
                case self::BASE64:      $value = $this->toBase64($value, $converter); break;
                case self::CUSTOM:
                    if (method_exists($repo, $converter['encode'])) {
                        $value = call_user_func_array([$repo, $converter['encode']], [$value, $converter]);
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
     * @param string $finder
     */
    public function postFind(Event $event, array &$results, $finder) {
        if (!in_array($finder, ['first', 'all'])) {
            return;
        }

        $repo = $this->getRepository();

        foreach ($results as $i => $result) {
            foreach ($result as $key => $value) {
                if (empty($this->_converters[$key])) {
                    continue;
                }

                $converter = $this->_converters[$key];

                // Exit if decoding should not happen
                if (!$converter['decode']) {
                    continue;
                }

                switch ($converter['type']) {
                    case self::SERIALIZE:   $value = $this->fromSerialize($value, $converter); break;
                    case self::JSON:        $value = $this->fromJson($value, $converter); break;
                    case self::BASE64:      $value = $this->fromBase64($value, $converter); break;
                    case self::CUSTOM:
                        if (method_exists($repo, $converter['decode'])) {
                            $value = call_user_func_array([$repo, $converter['decode']], [$value, $converter]);
                        }
                    break;
                }

                $results[$i][$key] = $value;
            }
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