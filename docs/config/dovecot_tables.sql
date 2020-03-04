CREATE TABLE IF NOT EXISTS `dovecot_users` (
    `userid` varchar(80) NOT NULL,
    `domain` varchar(80) NOT NULL DEFAULT '',
    `username` varchar(80) NOT NULL,
    `loginname` varchar(255) DEFAULT NULL,
    `password` varchar(100) NOT NULL,
    `quota_bytes` bigint(20) NOT NULL DEFAULT 2000,
    `quota_message` int(11) NOT NULL DEFAULT 0,
    `quota_sieve_bytes` bigint(20) NOT NULL DEFAULT 0,
    `quota_sieve_script` int(11) NOT NULL DEFAULT 0,
    `uid` varchar(20) DEFAULT NULL,
    `gid` varchar(20) DEFAULT NULL,
    `home` varchar(256) DEFAULT NULL,
    `last_login` datetime DEFAULT NULL,
    `last_login_unix` int(11) DEFAULT NULL,
    `instancename` varchar(40) DEFAULT NULL,
    PRIMARY KEY (`userid`,`domain`),
    UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `dovecot_usage` (
    `username` VARCHAR( 80 ) NOT NULL ,
    `storage`  BIGINT NOT NULL DEFAULT 0,
    `messages` BIGINT NOT NULL DEFAULT 0,
    PRIMARY KEY (`username`),
    CONSTRAINT `dovecot_usage::username--dovecot_users::username` FOREIGN KEY (`username`) REFERENCES `dovecot_users` (`username`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=Innodb DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `dovecot_master_users` (
    `username` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
    `password` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
    `service` enum('sieve') COLLATE utf8_unicode_ci DEFAULT 'sieve',
    PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
