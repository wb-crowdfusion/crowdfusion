<?php
/**
 * NodeAdminDAO
 *
 * PHP version 5
 *
 * Crowd Fusion
 * Copyright (C) 2009-2010 Crowd Fusion, Inc.
 * http://www.crowdfusion.com/
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted under the terms of the BSD License.
 *
 * @package     CrowdFusion
 * @copyright   2009-2010 Crowd Fusion Inc.
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version     $Id: NodeAdminDAO.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeAdminDAO
 *
 * @package     CrowdFusion
 */
class NodeAdminDAO
{

    protected $nodeSql = "
    CREATE TABLE IF NOT EXISTS `%Table%` (
      `%PrimaryKey%` int(11) unsigned NOT NULL auto_increment,
      `Slug` varchar(255) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,

      `ElementID` mediumint(9) unsigned NOT NULL default '0' COMMENT 'for reference only, not indexed',

      `Title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,

      `Status` enum( 'published', 'draft', 'deleted' ) NOT NULL default 'draft',
      `ActiveDate` timestamp NOT NULL default '0000-00-00 00:00:00',

      `CreationDate` timestamp NOT NULL default '0000-00-00 00:00:00',
      `ModifiedDate` timestamp NOT NULL default '0000-00-00 00:00:00',

      `SortOrder`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,

      `TreeID` varbinary(255) NOT NULL default '0',

      PRIMARY KEY  (`%PrimaryKey%`),
      KEY `Slug` (`Slug`,`Title`,`Status`,`ActiveDate`,`CreationDate`,`ModifiedDate`,`SortOrder`,`TreeID`),
      KEY `Status_ActiveDate` (`Status`,`ActiveDate`,`Slug`),
      KEY `Title` (`Title`,`Slug`),
      KEY `CreationDate` (`CreationDate`,`Slug`),
      KEY `SortOrder` (`SortOrder`,`Slug`),
      KEY `TreeID` (`TreeID`,`Slug`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;";


    protected $metaFlagSql = "
    CREATE TABLE IF NOT EXISTS `%Table%` (
      `MetaID` int(11) unsigned NOT NULL auto_increment,
      `%PrimaryKey%` int(11) unsigned NOT NULL default '0',
      `Name` varchar(64) NOT NULL default '',

      PRIMARY KEY  (`%PrimaryKey%`,`Name`),
      KEY `MetaID` (`MetaID`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;";


    protected $metaTinySql = "
    CREATE TABLE IF NOT EXISTS `%Table%` (
      `MetaID` int(11) unsigned NOT NULL auto_increment,
      `%PrimaryKey%` int(11) unsigned NOT NULL default '0',
      `Name` varchar(64) NOT NULL default '',
      `TinyValue` tinyint(3) %Signed% NOT NULL default '0',

      PRIMARY KEY  (`%PrimaryKey%`,`Name`),
      KEY `MetaID` (`MetaID`),
      KEY `Value` (`TinyValue`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;";


    protected $metaIntSql = "
    CREATE TABLE IF NOT EXISTS `%Table%` (
      `MetaID` int(11) unsigned NOT NULL auto_increment,
      `%PrimaryKey%` int(11) unsigned NOT NULL default '0',
      `Name` varchar(64) NOT NULL default '',
      `IntValue` int(11) %Signed% NOT NULL default '0',

      PRIMARY KEY  (`%PrimaryKey%`,`Name`),
      KEY `MetaID` (`MetaID`),
      KEY `Value` (`IntValue`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;";

    protected $metaLongSql = "
    CREATE TABLE IF NOT EXISTS `%Table%` (
      `MetaID` int(11) unsigned NOT NULL auto_increment,
      `%PrimaryKey%` int(11) unsigned NOT NULL default '0',
      `Name` varchar(64) NOT NULL default '',
      `LongValue` bigint(11) %Signed% NOT NULL default '0',

      PRIMARY KEY  (`%PrimaryKey%`,`Name`),
      KEY `MetaID` (`MetaID`),
      KEY `Value` (`LongValue`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;";


    protected $metaFloatSql = "
    CREATE TABLE IF NOT EXISTS `%Table%` (
      `MetaID` int(11) unsigned NOT NULL auto_increment,
      `%PrimaryKey%` int(11) unsigned NOT NULL default '0',
      `Name` varchar(64) NOT NULL default '',
      `FloatValue` float NOT NULL default '0',

      PRIMARY KEY  (`%PrimaryKey%`,`Name`),
      KEY `MetaID` (`MetaID`),
      KEY `Value` (`FloatValue`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;";

    protected $metaDateSql = "
    CREATE TABLE IF NOT EXISTS `%Table%` (
      `MetaID` int(11) unsigned NOT NULL auto_increment,
      `%PrimaryKey%` int(11) unsigned NOT NULL default '0',
      `Name` varchar(64) NOT NULL default '',
      `DateValue` timestamp NOT NULL default '0000-00-00 00:00:00',

      PRIMARY KEY  (`%PrimaryKey%`,`Name`),
      KEY `MetaID` (`MetaID`),
      KEY `Value` (`DateValue`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;";

    protected $metaDatetimeSql = "
    CREATE TABLE IF NOT EXISTS `%Table%` (
      `MetaID` int(11) unsigned NOT NULL auto_increment,
      `%PrimaryKey%` int(11) unsigned NOT NULL default '0',
      `Name` varchar(64) NOT NULL default '',
      `DatetimeValue` datetime NOT NULL default '0000-00-00 00:00:00',

      PRIMARY KEY  (`%PrimaryKey%`,`Name`),
      KEY `MetaID` (`MetaID`),
      KEY `Value` (`DatetimeValue`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;";

    protected $metaVarcharSql = "
    CREATE TABLE IF NOT EXISTS `%Table%` (
      `MetaID` int(11) unsigned NOT NULL auto_increment,
      `%PrimaryKey%` int(11) unsigned NOT NULL default '0',
      `Name` varchar(64) NOT NULL default '',
      `VarcharValue` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,

      PRIMARY KEY  (`%PrimaryKey%`,`Name`),
      KEY `MetaID` (`MetaID`),
      KEY `Value` (`VarcharValue`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;";

    protected $metaTextSql = "
    CREATE TABLE IF NOT EXISTS `%Table%` (
      `MetaID` int(11) unsigned NOT NULL auto_increment,
      `%PrimaryKey%` int(11) unsigned NOT NULL default '0',
      `Name` varchar(64) NOT NULL default '',
      `TextValue` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,

      PRIMARY KEY  (`%PrimaryKey%`,`Name`),
      KEY `MetaID` (`MetaID`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;";

    protected $metaBlobSql = "
    CREATE TABLE IF NOT EXISTS `%Table%` (
      `MetaID` int(11) unsigned NOT NULL auto_increment,
      `%PrimaryKey%` int(11) unsigned NOT NULL default '0',
      `Name` varchar(64) NOT NULL default '',
      `BlobValue` blob NOT NULL,

      PRIMARY KEY  (`%PrimaryKey%`,`Name`),
      KEY `MetaID` (`MetaID`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;";

    protected $metaMediumtextSql = "
    CREATE TABLE IF NOT EXISTS `%Table%` (
      `MetaID` int(11) unsigned NOT NULL auto_increment,
      `%PrimaryKey%` int(11) unsigned NOT NULL default '0',
      `Name` varchar(64) NOT NULL default '',
      `MediumtextValue` mediumtext CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,

      PRIMARY KEY  (`%PrimaryKey%`,`Name`),
      KEY `MetaID` (`MetaID`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;";

    protected $metaMediumblobSql = "
    CREATE TABLE IF NOT EXISTS `%Table%` (
      `MetaID` int(11) unsigned NOT NULL auto_increment,
      `%PrimaryKey%` int(11) unsigned NOT NULL default '0',
      `Name` varchar(64) NOT NULL default '',
      `MediumblobValue` mediumblob NOT NULL,

      PRIMARY KEY  (`%PrimaryKey%`,`Name`),
      KEY `MetaID` (`MetaID`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;";

//    protected $sectionsSql = "
//    CREATE TABLE IF NOT EXISTS `%Table%` (
//      `SectionID` int(11) unsigned NOT NULL auto_increment,
//      `%PrimaryKey%` int(11) unsigned NOT NULL default '0',
//      `Type` varchar(64) NOT NULL,
//      `Title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
//      `SortOrder` smallint(5) NOT NULL default '0',
//
//      PRIMARY KEY  (`%PrimaryKey%`, `SectionID`),
//      KEY `SectionID` (`SectionID`),
//      KEY `Type` (`Type`)
//    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;";

    protected $outTagsSql = "
    CREATE TABLE IF NOT EXISTS `%Table%` (
      `TagID` int(11) unsigned NOT NULL auto_increment,
      `%PrimaryKey%` int(11) unsigned NOT NULL default '0',

      `ElementID` mediumint(9) unsigned NOT NULL default '0',
      `Slug` varchar(255) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL default '',

      `Role` varchar(64) NOT NULL default '',
      `Value` varchar(255) NOT NULL default '',
      `ValueDisplay` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,

      `SortOrder` smallint(5) unsigned NOT NULL default '0',

      PRIMARY KEY  (`%PrimaryKey%`,`TagID`),
      KEY `TagID` (`TagID`),
      KEY `RoleOnly` (`Role`,`Value`),
      KEY `NodeRef` (`ElementID`, `Slug`, `Role`, `Value`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;";
    // KEY `Role` (`ElementID`, `Role`, `Value`)
    //  `RoleDisplay` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,

    protected $inTagsSql = "
    CREATE TABLE IF NOT EXISTS `%Table%` (
      `TagID` int(11) unsigned NOT NULL auto_increment,
      `%PrimaryKey%` int(11) unsigned NOT NULL default '0',

      `ElementID` mediumint(9) unsigned NOT NULL default '0',
      `Slug` varchar(255) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL default '',

      `Role` varchar(64) NOT NULL default '',
      `Value` varchar(255) NOT NULL default '',
      `ValueDisplay` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,

      `SortOrder` smallint(5) unsigned NOT NULL default '0',

      PRIMARY KEY  (`%PrimaryKey%`,`TagID`),
      KEY `TagID` (`TagID`),
      KEY `RoleOnly` (`Role`, `Value`),
      KEY `NodeRef` (`ElementID`, `Slug`, `Role`, `Value`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;";
    //      `TagOutID` int(11) unsigned NOT NULL,
//      KEY `OutTagID` (`ElementID`, `TagOutID`),

    //  KEY `Role` (`ElementID`, `Role`, `Value`)
    //  `Roledisplay` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,

    public function setNodeDataSource(DataSourceInterface $NodeDataSource)
    {
        $this->dsn = $NodeDataSource;
    }

    public function setNodeDBMeta(NodeDBMeta $nodeDBmeta)
    {
        $this->NodeDBMeta = $nodeDBmeta;
    }

    public function createDBSchema(Element $element)
    {
        $nodeRef = new NodeRef($element);

        $connections = $this->dsn->getConnectionsForReadWrite(array($nodeRef));
        foreach ( $connections as $connection ) {
            $db = $connection->getConnection();

            $this->writeDBTables($nodeRef, $db, $element);

        }
    }

    public function dropDBSchema(Element $element)
    {
        $nodeRef = new NodeRef($element);

        $connections = $this->dsn->getConnectionsForReadWrite(array($nodeRef));
        foreach ( $connections as $connection ) {
            $db = $connection->getConnection();

            $this->dropDBTables($nodeRef, $db, $element);

        }
    }

    protected function writeDBTables(NodeRef $nodeRef, DatabaseInterface $db, Element $element)
    {
        static $createdTables;

        // Look up each table, and grab the code for it if needed
        $table_map = array();

        $tableid = $this->NodeDBMeta->getPrimaryKey($nodeRef);

        // Node
        $table_map[$this->NodeDBMeta->getTableName($nodeRef)] = $this->nodeSql;

        // Out Tags
        $table_map[$this->NodeDBMeta->getOutTagsTable($nodeRef)] = $this->outTagsSql;

        // In Tags
        $table_map[$this->NodeDBMeta->getInTagsTable($nodeRef)] = $this->inTagsSql;

        // Sections
        // Only create if we have sections for this node
//        $sections = $element->getSchema()->getSectionDefs();
//        if (count($sections) > 0)
//            $table_map[$this->NodeDBMeta->getSectionsTable($nodeRef)] = $this->sectionsSql;

        $metaTypeMap = array('flag'   => $this->metaFlagSql,
                             'tiny'   => str_replace('%Signed% ', 'unsigned ', $this->metaTinySql),
                             'int'    => str_replace('%Signed% ', 'unsigned ', $this->metaIntSql),
                             'long'   => str_replace('%Signed% ', 'unsigned ', $this->metaLongSql),
                             'tiny-signed'   => str_replace('%Signed% ', '', $this->metaTinySql),
                             'int-signed'    => str_replace('%Signed% ', '', $this->metaIntSql),
                             'long-signed'   => str_replace('%Signed% ', '', $this->metaLongSql),
                             'float'  => $this->metaFloatSql,
                             'date'   => $this->metaDateSql,
                             'datetime'      => $this->metaDatetimeSql,
                             'varchar'  => $this->metaVarcharSql,
                             'text'     => $this->metaTextSql,
                             'mediumtext'     => $this->metaMediumtextSql,
                             'blob'   => $this->metaBlobSql,
                             'mediumblob'   => $this->metaMediumblobSql);

        // Look up what meta types we need to use
        $metaTypes = array();

        $metas = $element->getSchema()->getMetaDefs();
        foreach ( $metas as $meta )
            $metaTypes[] = $meta->Datatype;

//        foreach ( $sections as $sectionDef ) {
//            $metas = $sectionDef->getMetaDefs();
//            foreach ( $metas as $meta )
//                $metaTypes[] = $meta->Datatype;
//        }

        $metaTypes = array_unique($metaTypes);
        foreach ( $metaTypes as $metaType ) {
            if (empty($metaType))
                continue;

            if (!array_key_exists($metaType, $metaTypeMap))
                throw new Exception("Invalid type for meta: {$metaType}");

            $table_map[$this->NodeDBMeta->getMetaTable($nodeRef, $metaType)] = $metaTypeMap[$metaType];
        }


        // Find the tables that have been created.
        if(!$createdTables)
            $createdTables = $db->readCol('SHOW TABLES');

        // Create the tables that don't exist
        foreach ( $table_map as $table => $sql ) {
            if (!in_array($table, $createdTables)) {
                $sql = str_replace('%Table%', $table, $sql);
                $sql = str_replace('%PrimaryKey%', $tableid, $sql);
                $db->write($sql);
            }
        }
    }


    protected function dropDBTables(NodeRef $nodeRef, DatabaseInterface $db, Element $element)
    {
        static $createdTables;

        $tables[] = $this->NodeDBMeta->getTableName($nodeRef);
        $tables[] = $this->NodeDBMeta->getOutTagsTable($nodeRef);
        $tables[] = $this->NodeDBMeta->getInTagsTable($nodeRef);
//        $tables[] = $this->NodeDBMeta->getSectionsTable($nodeRef);

        foreach($this->NodeDBMeta->getMetaStorageDatatypes() as $dtype)
            $tables[] = $this->NodeDBMeta->getMetaTable($nodeRef, $dtype);


        // Find the tables that have been created.
        if(!$createdTables)
            $createdTables = $db->readCol('SHOW TABLES');

        // Create the tables that don't exist
        foreach ( $tables as $table) {
            if (in_array($table, $createdTables)) {
                $count = $db->readField('SELECT COUNT(*) FROM '.$db->quoteIdentifier($table));
                if($count == 0)
                {
                    $db->write('DROP TABLE '.$db->quoteIdentifier($table));
                }
            }
        }


    }


}