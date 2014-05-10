<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db\Behavior;

use Titon\Event\Event;
use Titon\Utility\Inflector;

/**
 * The SlugBehavior will generate a unique slug for each record before an insert or update occurs.
 *
 * @package Titon\Db\Behavior
 */
class SlugBehavior extends AbstractBehavior {

    /**
     * Configuration.
     *
     * @type array {
     *      @type string $field      The column to base the slug on
     *      @type string $slug       The column to write the slug to
     *      @type int $length        The max length of a slug
     *      @type bool $onUpdate     Will update the slug when a record is updated
     *      @type bool $unique       Whether to make the slug unique or not
     *      @type \Closure $scope    Additional query conditions when finding duplicates
     * }
     */
    protected $_config = [
        'field' => 'title',
        'slug' => 'slug',
        'length' => 255,
        'onUpdate' => true,
        'unique' => true,
        'scope' => null
    ];

    /**
     * Before a save occurs, generate a unique slug using another field as the base.
     * If no data exists, or the base doesn't exist, or the slug is already set, exit early.
     *
     * @param \Titon\Event\Event $event
     * @param int|int[] $id
     * @param array $data
     * @return bool
     */
    public function preSave(Event $event, $id, array &$data) {
        $config = $this->allConfig();

        if (empty($data) || empty($data[$config['field']]) || !empty($data[$config['slug']])) {
            return true;

        } else if ($id && !$config['onUpdate']) {
            return true;
        }

        $slug = $data[$config['field']];

        $this->getRepository()->emit('db.preSlug', [&$slug]);

        $slug = $this->slugify($slug);

        $this->getRepository()->emit('db.postSlug', [&$slug]);

        // Leave a gap of 3 to account for the appended numbers
        if (mb_strlen($slug) > ($config['length'] - 3)) {
            $slug = mb_substr($slug, 0, ($config['length'] - 3));
        }

        if ($config['unique']) {
            $slug = $this->makeUnique($id, $slug);
        }

        $data[$config['slug']] = $slug;

        return true;
    }

    /**
     * Validate the slug is unique by querying for other slugs.
     * If the slug is not unique, append a count to it.
     *
     * @param int|int[] $id
     * @param string $slug
     * @return string
     */
    public function makeUnique($id, $slug) {
        $repo = $this->getRepository();
        $scope = $this->getConfig('scope');

        /** @type \Titon\Db\Query $query */
        foreach ([
            $repo->select()->where($this->getConfig('slug'), $slug),
            $repo->select()->where($this->getConfig('slug'), 'like', $slug . '%')
        ] as $i => $query) {
            if ($scope) {
                $query->bindCallback($scope);
            }

            if ($id) {
                $query->where($repo->getPrimaryKey(), '!=', $id);
            }

            $count = $query->count();

            if ($count <= 0) {
                break;

            } else if ($i) {
                return $slug . '-' . $count;
            }
        }

        return $slug;
    }

    /**
     * Return a slugged version of a string.
     *
     * @param string $string
     * @return string
     */
    public static function slugify($string) {
        $string = strip_tags($string);
        $string = str_replace(['&amp;', '&'], 'and', $string);
        $string = str_replace('@', 'at', $string);

        // @codeCoverageIgnoreStart
        if (class_exists('Titon\G11n\Utility\Inflector')) {
            return \Titon\G11n\Utility\Inflector::slug($string);
        }
        // @codeCoverageIgnoreEnd

        return Inflector::slug($string);
    }

}