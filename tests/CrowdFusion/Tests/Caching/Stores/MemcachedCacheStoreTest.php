<?php

namespace CrowdFusion\Tests\Caching\Stores;

class MemcachedCacheStoreTest extends \PHPUnit_Framework_TestCase
{
    /** @var \MemcachedCacheStore */
    private $obj;

    /** @var string */
    private $prefix;

    /** @var string */
    private $persistentId = 'cf_memcached_tests';

    public function setup()
    {
        $this->prefix = 'test_' . md5(rand()) . '_';

        if (!class_exists('Memcached')) {
            Mock\MemcachedMock::create();
        }

        $this->obj = new \MemcachedCacheStore(
            new \NullLogger(),
            [['host' => '127.0.0.1', 'port' => 11211]],
            $this->prefix,
            true,
            $this->persistentId,
            false,
            false,
            false,
            false
        );
    }

    public function testPutAndGet()
    {
        $this->obj->put('fruit', 'apple', 30);
        $result = $this->obj->get('fruit');
        $this->assertEquals('apple', $result);
    }

    public function testJsonPutAndGet()
    {
        $json = json_encode(new SimpleObject());

        $this->obj->put('fruitJson', $json, 30);
        $result = $this->obj->get('fruitJson');
        $fruitObj = json_decode($result);
        $this->assertEquals('peach', $fruitObj->param1);
    }

    public function testObjPutAndGet()
    {
        $newFruits = new SimpleObject();

        $this->obj->put('fruitObj', $newFruits, 30);
        $result = $this->obj->get('fruitObj');
        $this->assertEquals('peach', $result->param1);
        $this->assertEquals(3, $result->getParam2());

        $newFruits->param1 = 'apple';
        $this->obj->put('fruitObj2', $newFruits, 30);

        $result = $this->obj->multiGet(array('fruitObj', 'fruitObj2'));

        $this->assertEquals('peach', $result['fruitObj']->param1);
        $this->assertEquals(3, $result['fruitObj']->getParam2());
        $this->assertEquals('apple', $result['fruitObj2']->getParam1());
        $this->assertEquals(3, $result['fruitObj2']->getParam2());
    }

    public function testArrayGetPut()
    {
        $fruitArray = array('fruit' => array('apple', 'peach'), 'notFruit' => array('bacon'));

        $this->obj->put('fruitArray', $fruitArray, 30);
        $result = $this->obj->get('fruitArray');
        $this->assertContains('peach', $result['fruit']);
        $this->assertContains('bacon', $result['notFruit']);
        $this->assertCount(2, $result);

        $fruitArray2 = array('fruit' => array("apple", "peach"), 'notFruit' => array('bacon', 'glass'));
        $this->obj->put('fruitArray2', $fruitArray2, 30);

        $result = $this->obj->multiGet(array('fruitArray', 'fruitArray2'));
        $this->assertCount(2, $result);
        $this->assertContains('peach', $result['fruitArray']['fruit']);
        $this->assertContains('bacon', $result['fruitArray']['notFruit']);
        $this->assertCount(1, $result['fruitArray']['notFruit']);
        $this->assertContains('peach', $result['fruitArray2']['fruit']);
        $this->assertContains('glass', $result['fruitArray2']['notFruit']);
        $this->assertCount(2, $result['fruitArray2']);
    }

    public function testMultiGet()
    {
        $this->obj->put('test1', 'apple', 0);
        $this->obj->put('test2', 'jacks', 0);
        $result = $this->obj->multiGet(array('test1', 'test2'));
        $this->assertEquals('apple', $result['test1']);
        $this->assertEquals('jacks', $result['test2']);

        // mutli mixed
        $fruitArray = array('fruit' => array("apple", "peach"), 'notFruit' => array('bacon'));
        $newFruits = new SimpleObject();

        $this->obj->put('fruitArray', $fruitArray, 30);
        $this->obj->put('fruitObj', $newFruits, 30);

        $result = $this->obj->multiGet(array('fruitArray', 'fruitObj'));

        $this->assertContains('peach', $result['fruitArray']['fruit']);
        $this->assertContains('bacon', $result['fruitArray']['notFruit']);
        $this->assertCount(2, $result['fruitArray']);

        $this->assertEquals('peach', $result['fruitObj']->param1);
        $this->assertEquals(3, $result['fruitObj']->getParam2());
    }

    public function testPut()
    {
        $this->obj->put('fruit', 'apple', 30);
        $result = $this->obj->get('fruit');
        $this->assertEquals('apple', $result);

        // test $has_replaced check by writing twice
        $this->obj->put('fruit', 'peach', 30);

        $result = $this->obj->get('fruit');
        $this->assertEquals('peach', $result);
    }

    public function testAdd()
    {
        $this->obj->add('fruit', 'apple', 30);
        $result = $this->obj->get('fruit');
        $this->assertEquals('apple', $result);

        // test $has_replaced check by writing twice
        $this->obj->delete('fruit');
        $this->obj->add('fruit', 'peach', 30);

        $result = $this->obj->get('fruit');
        $this->assertEquals('peach', $result);
    }

    public function testContainsKey()
    {
        $result = $this->obj->containsKey('apple');
        $this->assertFalse($result);
        $this->obj->put('fruit', 'apple', 30);
        $result = $this->obj->containsKey('fruit');
        $this->assertTrue($result);
    }

    public function testGetCachedObject()
    {
        $result = $this->obj->getCachedObject('apple');
        $this->assertFalse($result);
        $this->obj->put('fruit', 'apple', 30);
        $result = $this->obj->getCachedObject('fruit');
        $this->assertEquals('fruit', $result->getKey());
        $this->assertEquals('apple', $result->getValue());
        $this->assertEquals(30, $result->getExpirationTime() - $result->getCreationTime());
        $this->assertEquals(30, $result->getDuration());

        $string = (string) $result;
        $this->assertRegExp("/" . $result->getExpirationTime() . "/", $string);
    }

    public function testUpdateDuration()
    {
        $this->obj->put('fruit', 'apple', 30);
        $result = $this->obj->getCachedObject('fruit');
        $this->assertEquals(30, $result->getDuration());

        $this->obj->updateDuration('fruit', 70);
        $result = $this->obj->getCachedObject('fruit');
        $this->assertEquals(70, $result->getDuration());
        $result = $this->obj->get('fruit');
        $this->assertEquals('apple', $result);
    }

    public function testExpireAll()
    {
        $this->obj->put('fruit', 'apple', 30);
        $this->obj->put('fruit', 'peach', 30);

        $this->obj->expireAll();
        $result = $this->obj->get('fruit');
        $this->assertFalse($result);
    }

    public function testIncrement()
    {
        // todo: write increment test (restore mock increment/decrement after fixes)
        /*
        $result = $this->obj->increment('testerment', 1);
        $this->assertEquals(1, $result);
        $result = $this->obj->getIncrement('testerment');
        $this->assertEquals(1, $result);
        $result = $this->obj->increment('testerment');
        $this->assertEquals(2, $result);
        $result = $this->obj->getIncrement('testerment');
        $this->assertEquals(2, $result);
        $result = $this->obj->increment('testerment', 5);
        $this->assertEquals(7, $result);
        $result = $this->obj->getIncrement('testerment');
        $this->assertEquals(7, $result);
        */
    }

    public function testDecrement()
    {
        // todo: write decrement test
        /*
        $result = $this->obj->decrement('testerment', 3);
        $this->assertEquals(4, $result);
        $result = $this->obj->getIncrement('testerment');
        $this->assertEquals(4, $result);
        */
    }
}
