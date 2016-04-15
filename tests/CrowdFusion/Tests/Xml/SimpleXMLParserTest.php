<?php

namespace CrowdFusion\Tests\Xml;

class SimpleXMLParserTest extends \PHPUnit_Framework_TestCase
{
    /** @var \SimpleXMLParser\ */
    private $parser;

    public function setUp()
    {
        $this->parser = new \SimpleXMLParser();
    }

    public function tearDown()
    {
        $this->parser = null;
    }

    /**
     * @dataProvider getValidXmls
     *
     * @param string $string
     * @param bool   $exception
     */
    public function testParseXMLString($string)
    {
        $doc = $this->parser->parseXMLString($string);

        $this->assertInstanceOf('SimpleXMLExtended', $doc);
    }

    /**
     * @dataProvider getInvalidXmls
     * @expectedException \SimpleXMLParserException
     *
     * @param string $string
     */
    public function testParseBadXMLString($string)
    {
        $doc = $this->parser->parseXMLString($string);
    }

    /**
     * @return array
     */
    public function getValidXmls()
    {
        return array(
            array(
                'string' => '<xml></xml>'
            ),
            array(
                'string' => '<xml><metadata/></xml>'
            ),
            array(
                'string' => '<xml><metadata><entryId>123123</entryId></metadata></xml>'
            ),
            array(
                'string' => '<xml><metadata><entryId>123123</entryId><createdAt>'.time().'</createdAt><updatedAt>'.time().'</updatedAt></metadata></xml>'
            )
        );
    }

    /**
     * @return array
     */
    public function getInvalidXmls()
    {
        return array(
            array(
                'string' => 'bad xml'
            ),
            array(
                'string' => '<xml><metadata><entryId>123123</entryId></xml>'
            )
        );
    }
}
