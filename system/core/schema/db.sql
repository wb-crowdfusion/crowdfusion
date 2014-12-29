--
-- Table structure for table `sysversion`
--

CREATE TABLE `sysversion` (
    `Version`           MEDIUMINT(9)    UNSIGNED    NOT NULL    AUTO_INCREMENT,
	`CFVersion`			VARCHAR(32),
    `CreationDate`      TIMESTAMP                   NOT NULL    DEFAULT '0000-00-00 00:00:00',
    `Details`           TEXT,
    `Backtrace`         TEXT,
    PRIMARY KEY             (`Version`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;