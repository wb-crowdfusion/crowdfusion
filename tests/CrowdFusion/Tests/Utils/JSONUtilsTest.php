<?php

namespace CrowdFusion\Tests\Utils;

class JSONUtilsTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider getEmptyJson
     *
     * @param string $string
     */
    public function testEmptyIsValid($string)
    {
        $this->assertTrue(\JSONUtils::isValid($string));
    }

    /**
     * @dataProvider getTestArray
     *
     * @param array
     */
    public function testIsValid(array $phpArray)
    {
        $json = json_encode($phpArray);

        $this->assertTrue(\JSONUtils::isValid($json));
    }

    /**
     * @dataProvider getInvalidJsons
     *
     * @param string $string
     */
    public function testIsNotValid($string)
    {
        $this->assertNotTrue(\JSONUtils::isValid($string));
    }

    /**
     * @dataProvider getTestArray
     *
     * @param array $phpArray
     * @return string valid json string
     */
    public function testEncode(array $phpArray)
    {
        $jsonString = \JSONUtils::encode($phpArray, false, false);

        $this->assertJsonStringEqualsJsonString($jsonString, json_encode($phpArray));

        return $jsonString;
    }

    /**
     * @dataProvider getTestArray
     *
     * @param array $phpArray
     */
    public function testDecodeValidJsonString(array $phpArray)
    {
        $jsonString = json_encode($phpArray);

        $decodeToArray = \JSONUtils::decode($jsonString, true);

        $this->assertArrayHasKey('foo',   $decodeToArray);
        $this->assertArrayHasKey('bar',   $decodeToArray);
        $this->assertArrayHasKey('baz',   $decodeToArray);
        $this->assertArrayHasKey('space', $decodeToArray);
        $this->assertArrayHasKey('tags',  $decodeToArray);
    }

    /**
     * @dataProvider getInvalidJsons
     * @expectedException \JSONException
     *
     * @param string $string
     */
    public function testDecodeBadJsonString($string)
    {
        $rst = \JSONUtils::decode($string);
    }

    /**
     * @dataProvider getTestArray
     *
     * @param array $phpArray
     */
    public function testFormat(array $phpArray)
    {
        $regularJson    = json_encode($phpArray);
        $prettyJson     = \JSONUtils::format($regularJson);
        $prettyJsonHtml = \JSONUtils::format($regularJson, true);

        $this->assertJsonStringEqualsJsonString(\JSONUtils::encode($phpArray, true), $prettyJson);

        $this->assertJsonStringNotEqualsJsonString($regularJson, $prettyJson);

        $this->assertNotRegExp("/(<br\ ?\/?>)+/", $prettyJson);

        $this->assertRegExp("/(<br\ ?\/?>)+/", $prettyJsonHtml);
    }

    /**
     * @return array empty strings
     */
    public function getEmptyJson()
    {
        return array(
            array('string' => ''),
            array('string' => '{}'),
            array('string' => '[]'),
            array('string' => null)
        );
    }

    /**
     * @return array contents invalid json strings
     */
    public function getInvalidJsons()
    {
        return array(
            array('string' => 'bad json'),
            array('string' => '[{bad json]'),
            array('string' => '{bad, json}'),
            array('string' => '{"bad": "json"},')
        );
    }

    /**
     * @dataProvider getTestTypes
     *
     * @param array  $phpArray
     * @param string $json
     * @param string $pretty
     */
    public function testCompareTypes(array $phpArray, $json, $pretty)
    {
        $this->assertJsonStringEqualsJsonString(\JSONUtils::encode($phpArray), $json);
        $this->assertJsonStringEqualsJsonString(\JSONUtils::encode($phpArray, true), $pretty);
        $this->assertJsonStringEqualsJsonString(\JSONUtils::format($json), $pretty);
    }

    /**
     * @return array
     */
    public function getTestArray()
    {
        return array(
            array(
                array(
                    'foo' => 'look, here are the com,mas,',
                    'bar' => 'and " so\'me single &&& do\\uble """ qou,t@#$%^&*()es!',
                    'baz' => array(
                        'test' => 'from json [] enc\'".,ode php'
                    ),
                    'space' => 'trailing spaces             ', // <-- trailing spaces
                    "tags" => "web,mbr,dash,,,,           ", // <--- extra comma at the end
                )
            )
        );
    }

    /**
     * @return array
     */
    public function getTestTypes()
    {
        return array(
            array(
                'array' => array(
                    'a' => '123',
                    'b' => 456,
                    'c' => array(
                        'a', 'b', 'c'
                    )
                ),
                'json' => '{"a":"123","b":456,"c":["a","b","c"]}',
                'pretty' => '{
  "a": "123",
  "b": 456,
  "c": [
    "a",
    "b",
    "c"
  ]
}',
            )
        );
    }
}
