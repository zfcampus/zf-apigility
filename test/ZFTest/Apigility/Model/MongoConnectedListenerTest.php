<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility\Model;

use MongoClient;
use MongoCollection;
use MongoDB;
use PHPUnit_Framework_TestCase as TestCase;
use ZF\Apigility\Model\MongoConnectedListener;

class MongoConnectedListenerTest extends TestCase
{
    protected static $mongoDb;

    protected static $lastId;

    public function setUp()
    {
        $this->markTestSkipped('Mongo-connected functionality is not currently working');

        if (!extension_loaded('mongo')) {
            $this->markTestSkipped(
                'The MongoDB extension is not available.'
            );
        }

        $m  = new \MongoClient();
        static::$mongoDb = $m->selectDB("test_zf_apigility_mongoconnected");
        $collection = new \MongoCollection(static::$mongoDb, 'test');

        $this->mongoListener = new MongoConnectedListener($collection);
    }

    public static function tearDownAfterClass()
    {
        if (static::$mongoDb instanceof MongoDB) {
            static::$mongoDb->drop();
        }
    }

    public function testCreate()
    {
        $data = array( 'foo' => 'bar' );
        $result = $this->mongoListener->create($data);
        $this->assertTrue(isset($result['_id']));
        static::$lastId = $result['_id'];
    }

    public function testPatch()
    {
        if (empty(static::$lastId)) {
            $this->markTestIncomplete(
                'This test cannot be executed.'
            );
        }
        $data = array ( 'foo' => 'baz' );
        $this->assertTrue($this->mongoListener->patch(static::$lastId, $data));
    }

    public function testFetch()
    {
        if (empty(static::$lastId)) {
            $this->markTestIncomplete(
                'This test cannot be executed.'
            );
        }
        $result = $this->mongoListener->fetch(static::$lastId);
        $this->assertTrue(!empty($result));
        $this->assertEquals(static::$lastId, $result['_id']);
    }

    public function testFetchAll()
    {
        $num = 3;
        for ($i=0; $i < $num; $i++) {
            $this->mongoListener->create(array(
                'foo'   => 'bau',
                'count' => $i
            ));
        }
        $data = array( 'foo' => 'bau' );
        $result = $this->mongoListener->fetchAll($data);
        $this->assertTrue(!empty($result));
        $this->assertTrue(is_array($result));
        $this->assertEquals($num, count($result));
    }

    public function testDelete()
    {
        if (empty(static::$lastId)) {
            $this->markTestIncomplete(
                'This test cannot be executed.'
            );
        }
        $result = $this->mongoListener->delete(self::$lastId);
        $this->assertTrue($result);
    }

}
