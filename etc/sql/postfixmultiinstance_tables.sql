CREATE TABLE IF NOT EXISTS `smtp_users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `userid` varchar(40) NOT NULL,
    `client_idnr` varchar(40) DEFAULT NULL,
    `username` varchar(80) NOT NULL,
    `passwd` varchar(256) NOT NULL,
    `email` varchar(80) DEFAULT NULL,
    `forward_only` tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `userid-client_idnr` (`userid`,`client_idnr`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `smtp_destinations` (
    `users_id` int(11) NOT NULL,
    `source` varchar(80) NOT NULL,
    `destination` varchar(80) NOT NULL,
    KEY `users_id` (`users_id`),
    KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `smtp_destinations`
ADD CONSTRAINT `smtp_destinations_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `smtp_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE `smtp_virtual_domains` (
    `domain` varchar(50) NOT NULL,
    `instancename` varchar(40) NOT NULL,
    PRIMARY KEY (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;