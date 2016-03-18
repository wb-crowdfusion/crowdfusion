<?php

namespace CrowdFusion\Tests\Utils;

class FileSystemUtilsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getMimetypes
     *
     * @param string $extension
     * @param string $mimetype
     */
    public function testGetMimetype($extension, $mimetype)
    {
        $this->assertEquals($mimetype, \FileSystemUtils::getMimetype($extension));
    }

    /**
     * @dataProvider getBadExtensions
     *
     * @param string $extension
     */
    public function testGetMimetypeBadExtensions($extension)
    {
        $this->assertEquals('application/octet-stream', \FileSystemUtils::getMimetype($extension));
    }

    /**
     * @return array
     */
    public function getMimetypes()
    {
        $mimetypes = [];

        foreach (\FileSystemUtils::MIME_TYPES as $extension => $mimetype) {
            $mimetypes[] = [
                'extension' => $extension,
                'mimetype' => $mimetype,
            ];
        }

        return $mimetypes;
    }

    /**
     * @return array
     */
    public function getBadExtensions()
    {
        $extensions = [];

        for ($i=0; $i<10; $i++) {
            $extensions[] = [
                'extension' => substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4),
            ];
        }

        return $extensions;
    }
}
