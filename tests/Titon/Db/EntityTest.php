<?php
/**
 * @copyright   2010-2014, The Titon Project
 * @license     http://opensource.org/licenses/bsd-license.php
 * @link        http://titon.io
 */

namespace Titon\Db;

use Titon\Db\Entity;
use Titon\Test\TestCase;

/**
 * Test class for Titon\Db\Entity.
 *
 * @property \Titon\Db\Entity $object
 */
class EntityTest extends TestCase {

    /**
     * Create complex entity object.
     */
    protected function setUp() {
        parent::setUp();

        $this->object = new Entity([
            'id' => 1,
            'username' => 'Miles',
            'password' => 'iamasecret',
            'email' => 'email@domain.com',
            'created' => '1988-02-26 10:22:33',
            'interests' => ['food', 'games'],
            'Profile' => new Entity([
                'id' => 1,
                'age' => 25,
                'country' => 'USA'
            ]),
            'Posts' => new EntityCollection([
                new Entity([
                    'id' => 666,
                    'title' => 'Post #1'
                ]),
                new Entity([
                    'id' => 1337,
                    'title' => 'Post #2'
                ])
            ])
        ]);
    }

    /**
     * Test that property access works.
     */
    public function testMapData() {
        $this->assertEquals(1, $this->object->id);
        $this->assertEquals(null, $this->object->age);

        // Isset
        $this->assertTrue(isset($this->object->username));
        $this->assertFalse(isset($this->object->name));

        // Nested
        $this->assertEquals(25, $this->object->Profile->age);
        $this->assertEquals('Post #1', $this->object->Posts[0]->title);
    }

    /**
     * Test that array access works.
     */
    public function testArrayAccess() {
        $this->assertEquals(1, $this->object['id']);
        $this->assertEquals(null, $this->object['age']);

        // Isset
        $this->assertTrue(isset($this->object['username']));
        $this->assertFalse(isset($this->object['name']));

        // Nested
        $this->assertEquals(25, $this->object['Profile']['age']);
        $this->assertEquals('Post #1', $this->object['Posts'][0]['title']);
    }

    public function testSerialize() {
        $data = serialize($this->object);
        $this->assertEquals('C:15:"Titon\Db\Entity":525:{a:8:{s:2:"id";i:1;s:8:"username";s:5:"Miles";s:8:"password";s:10:"iamasecret";s:5:"email";s:16:"email@domain.com";s:7:"created";s:19:"1988-02-26 10:22:33";s:9:"interests";a:2:{i:0;s:4:"food";i:1;s:5:"games";}s:7:"Profile";C:15:"Titon\Db\Entity":58:{a:3:{s:2:"id";i:1;s:3:"age";i:25;s:7:"country";s:3:"USA";}}s:5:"Posts";C:25:"Titon\Db\EntityCollection":165:{a:2:{i:0;C:15:"Titon\Db\Entity":47:{a:2:{s:2:"id";i:666;s:5:"title";s:7:"Post #1";}}i:1;C:15:"Titon\Db\Entity":48:{a:2:{s:2:"id";i:1337;s:5:"title";s:7:"Post #2";}}}}}}', $data);

        $entity = unserialize($data);
        $this->assertEquals($this->object, $entity);
    }

    public function testJsonSerialize() {
        $this->assertEquals('{"id":1,"username":"Miles","password":"iamasecret","email":"email@domain.com","created":"1988-02-26 10:22:33","interests":["food","games"],"Profile":{"id":1,"age":25,"country":"USA"},"Posts":[{"id":666,"title":"Post #1"},{"id":1337,"title":"Post #2"}]}', json_encode($this->object));
    }

    /**
     * Test looping works.
     */
    public function testIterator() {
        $keys = [];

        foreach ($this->object as $key => $value) {
            $keys[] = $key;
        }

        $this->assertEquals($keys, $this->object->keys());
    }

    /**
     * Test that closures are executed and the value is returned.
     */
    public function testClosureReading() {
        $this->object = new Entity([
            'id' => 1,
            'username' => function() {
                return 'Miles';
            }
        ]);

        $this->assertEquals('Miles', $this->object->username);
        $this->assertEquals([
            'id' => 1,
            'username' => 'Miles'
        ], $this->object->toArray());
    }

    public function testToJson() {
        $this->assertEquals('{"id":1,"username":"Miles","password":"iamasecret","email":"email@domain.com","created":"1988-02-26 10:22:33","interests":["food","games"],"Profile":{"id":1,"age":25,"country":"USA"},"Posts":[{"id":666,"title":"Post #1"},{"id":1337,"title":"Post #2"}]}', $this->object->toJson());
    }

    /**
     * Test nested entities are converted to arrays.
     */
    public function testNestedToArray() {
        $this->assertEquals([
            'id' => 1,
            'username' => 'Miles',
            'password' => 'iamasecret',
            'email' => 'email@domain.com',
            'created' => '1988-02-26 10:22:33',
            'interests' => ['food', 'games'],
            'Profile' => [
                'id' => 1,
                'age' => 25,
                'country' => 'USA'
            ],
            'Posts' => [
                [
                    'id' => 666,
                    'title' => 'Post #1'
                ],
                [
                    'id' => 1337,
                    'title' => 'Post #2'
                ]
            ]
        ], $this->object->toArray());
    }

}