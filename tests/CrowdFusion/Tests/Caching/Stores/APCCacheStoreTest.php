<?php

namespace CrowdFusion\Tests\Caching\Stores;

class APCCacheStoreTest extends \PHPUnit_Framework_TestCase
{
    /** @var \APCCacheStore */
    private $obj;

    public function setup()
    {
        $this->obj = new \APCCacheStore(new \NullLogger(), '', true);
    }

    public function testContainsKey()
    {
        $this->assertFalse($this->obj->containsKey('apple'));

        $this->obj->put('fruit', 'apple', 30);
        $this->assertTrue($this->obj->containsKey('fruit'));
    }

    public function testPutAndGet()
    {
        $this->obj->put('fruit', 'apple', 30);
        $this->assertEquals('apple', $this->obj->get('fruit'));
    }

    public function testMultiGet()
    {
        $this->obj->put('test1', 'apple', 0);
        $this->obj->put('test2', 'jacks', 0);

        $result = $this->obj->multiGet(array('test1', 'test2'));

        $this->assertEquals('apple', $result['test1']);
        $this->assertEquals('jacks', $result['test2']);

        // mutli mixed

        $fruitArray = array(
            'fruit' => array(
                'apple',
                'peach'
            ),
            'notFruit' => array(
                'bacon'
            )
        );

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

    public function testGetCachedObject()
    {
        $this->assertFalse($this->obj->getCachedObject('apple'));

        $this->obj->put('fruit', 'apple', 30);
        $result = $this->obj->getCachedObject('fruit');
        $this->assertEquals('fruit', $result->getKey());
        $this->assertEquals('apple', $result->getValue());
        $this->assertEquals(30, $result->getExpirationTime() - $result->getCreationTime());
        $this->assertEquals(30, $result->getDuration());

        $string = (string) $result;
        $this->assertRegExp(sprintf('/%s/', $result->getExpirationTime()), $string);
    }

    public function testUpdateDuration()
    {
        $this->obj->put('fruit', 'apple', 30);
        $this->obj->updateDuration('fruit', 70);

        $this->assertEquals(70, $this->obj->getCachedObject('fruit')->getDuration());
        $this->assertEquals('apple', $this->obj->get('fruit'));
    }

    public function testDelete()
    {
        $this->obj->put('fruit', 'apple', 30);
        $this->assertEquals('apple', $this->obj->get('fruit'));

        $this->obj->delete('fruit');
        $this->assertFalse($this->obj->get('fruit'));
    }

    public function testExpireAll()
    {
        $this->obj->put('fruit', 'apple', 30);
        $this->obj->expireAll();

        $this->assertFalse($this->obj->get('fruit'));
    }
}
