<?php

namespace CrowdFusion\Tests\Xml;

class SimpleXMLExtendedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getValidXmls
     *
     * @param string $string
     */
    public function testAsPrettyXML($input, $output)
    {
        $xml = simplexml_load_string($input, '\SimpleXMLExtended');
        $formatted = $xml->asPrettyXML();

        $this->assertEquals($output, $formatted);
    }

    /**
     * @return array
     */
    public function getValidXmls()
    {
        $time = time();

        return array(
            array(
                'input' => '<xml></xml>',
                'output' => "<?xml version=\"1.0\"?>
<xml/>
"
            ),
            array(
                'input' => '<xml><metadata/></xml>',
                'output' => "<?xml version=\"1.0\"?>
<xml>
  <metadata/>
</xml>
"
            ),
            array(
                'input' => '<xml><metadata><entryId>123123</entryId></metadata></xml>',
                'output' => "<?xml version=\"1.0\"?>
<xml>
  <metadata>
    <entryId>123123</entryId>
  </metadata>
</xml>
"
            ),
            array(
                'input' => '<xml><metadata><entryId>123123</entryId><createdAt>'.$time.'</createdAt><updatedAt>'.$time.'</updatedAt></metadata></xml>',
                'output' => "<?xml version=\"1.0\"?>
<xml>
  <metadata>
    <entryId>123123</entryId>
    <createdAt>".$time."</createdAt>
    <updatedAt>".$time."</updatedAt>
  </metadata>
</xml>
"
            )
        );
    }
}
