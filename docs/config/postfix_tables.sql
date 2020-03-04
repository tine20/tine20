CREATE TABLE IF NOT EXISTS `smtp_users` (
    `userid` varchar(40) NOT NULL,
    `client_idnr` varchar(40) NOT NULL,
    `username` varchar(80) NOT NULL,
    `passwd` varchar(256) NOT NULL,
    `email` varchar(80) DEFAULT NULL,
    `forward_only` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`userid`, `client_idnr`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `smtp_destinations` (
    `userid` VARCHAR( 40 ) NOT NULL ,
    `source` VARCHAR( 80 ) NOT NULL ,
    `destination` VARCHAR( 80 ) NOT NULL ,
    `dispatch_address` tinyint(1) NOT NULL DEFAULT 1,
    CONSTRAINT `smtp_destinations::userid--smtp_users::userid` FOREIGN KEY (`userid`)
    REFERENCES `smtp_users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=Innodb DEFAULT CHARSET=utf8;
