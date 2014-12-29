<?php
/**
 * XML2Assoc Class to creating
 * PHP Assoc Array from XML File
 *
 * @author godseth (AT) o2.pl & rein_baarsma33 (AT) hotmail.com (Bugfixes in parseXml Method)
 * @uses XMLReader
 *
 */

class Xml2Assoc {

    /**
     * Method for loading XML Data from String
     *
     * @param string $sXml
     * @param bool $bOptimize
     */

    public static function parseString( $sXml , $bOptimize = false) {
        $oXml = new XMLReader();
        $this -> bOptimize = (bool) $bOptimize;
        try {

            // Set String Containing XML data
            $oXml->XML($sXml);

            // Parse Xml and return result
            return self::parseXml($oXml, $bOptimize);

        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Method for loading Xml Data from file
     *
     * @param string $sXmlFilePath
     * @param bool $bOptimize
     */
    public static function parseFile( $sXmlFilePath , $bOptimize = false ) {
        $oXml = new XMLReader();
        try {
            // Open XML file
            $oXml->open($sXmlFilePath);

            // // Parse Xml and return result
            return self::parseXml($oXml, $bOptimize);

        } catch (Exception $e) {
            echo $e->getMessage(). ' | Try open file: '.$sXmlFilePath;
        }
    }

    /**
     * XML Parser
     *
     * @param XMLReader $oXml
     * @param bool $bOptimize
     * @return array
     */
    public static function parseXml( XMLReader $oXml, $bOptimize = false ) {

        $aAssocXML = null;
        $iDc = -1;

        while($oXml->read()){
            switch ($oXml->nodeType) {

                case XMLReader::END_ELEMENT:

                    if ($bOptimize) {
                        self::optXml($aAssocXML);
                    }
                    return $aAssocXML;

                case XMLReader::ELEMENT:

                    if(!isset($aAssocXML[$oXml->name])) {
                        if($oXml->hasAttributes) {
                            $aAssocXML[$oXml->name][] = $oXml->isEmptyElement ? '' : self::parseXML($oXml);
                        } else {
                            if($oXml->isEmptyElement) {
                                $aAssocXML[$oXml->name] = '';
                            } else {
                                $aAssocXML[$oXml->name] = self::parseXML($oXml);
                            }
                        }
                    } elseif (is_array($aAssocXML[$oXml->name])) {
                        if (!isset($aAssocXML[$oXml->name][0]))
                        {
                            $temp = $aAssocXML[$oXml->name];
                            foreach ($temp as $sKey=>$sValue)
                            unset($aAssocXML[$oXml->name][$sKey]);
                            $aAssocXML[$oXml->name][] = $temp;
                        }

                        if($oXml->hasAttributes) {
                            $aAssocXML[$oXml->name][] = $oXml->isEmptyElement ? '' : self::parseXML($oXml);
                        } else {
                            if($oXml->isEmptyElement) {
                                $aAssocXML[$oXml->name][] = '';
                            } else {
                                $aAssocXML[$oXml->name][] = self::parseXML($oXml);
                            }
                        }
                    } else {
                        $mOldVar = $aAssocXML[$oXml->name];
                        $aAssocXML[$oXml->name] = array($mOldVar);
                        if($oXml->hasAttributes) {
                            $aAssocXML[$oXml->name][] = $oXml->isEmptyElement ? '' : self::parseXML($oXml);
                        } else {
                            if($oXml->isEmptyElement) {
                                $aAssocXML[$oXml->name][] = '';
                            } else {
                                $aAssocXML[$oXml->name][] = self::parseXML($oXml);
                            }
                        }
                    }

                    if($oXml->hasAttributes) {
                        $mElement =& $aAssocXML[$oXml->name][count($aAssocXML[$oXml->name]) - 1];
                        while($oXml->moveToNextAttribute()) {
                            $mElement[$oXml->name] = $oXml->value;
                        }
                    }
                    break;
                case XMLReader::TEXT:
                case XMLReader::CDATA:

                    $aAssocXML[++$iDc] = $oXml->value;

            }
        }

        return $aAssocXML;
    }

    /**
     * Method to optimize assoc tree.
     * ( Deleting 0 index when element
     *  have one attribute / value )
     *
     * @param array $mData
     */
    public static function optXml(&$mData) {
        if (is_array($mData)) {
            if (isset($mData[0]) && count($mData) == 1 ) {
                $mData = $mData[0];
                if (is_array($mData)) {
                    foreach ($mData as &$aSub) {
                        self::optXml($aSub);
                    }
                }
            } else {
                foreach ($mData as &$aSub) {
                    self::optXml($aSub);
                }
            }
        }
    }

}

?>