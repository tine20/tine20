CREATE TABLE IF NOT EXISTS `syncope_devices` (
    `id` varchar(40) NOT NULL, `name`,
    `deviceid` varchar(64) NOT NULL,                                                                                                                                                                         
    `devicetype` varchar(64) NOT NULL,
    `policykey` varchar(64) DEFAULT NULL,
    `owner_id` varchar(40) NOT NULL,
    `acsversion` varchar(40) NOT NULL,
    `pinglifetime` int(11) DEFAULT NULL,
    `remotewipe` int(11) DEFAULT '0',
    PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `syncope_folderstates` (
  `id` varchar(40) NOT NULL,
  `device_id` varchar(64) NOT NULL,
  `class` varchar(64) NOT NULL,
  `folderid` varchar(254) NOT NULL,
  `creation_time` datetime NOT NULL,
  `lastfiltertype` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_id--class--folderid` (`device_id`(40),`class`(40),`folderid`(40)),
  KEY `folderstates::device_id--devices::id` (`device_id`),
  CONSTRAINT `folderstates::device_id--devices::id` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE 
);

CREATE TABLE `syncope_synckeys` (
  `device_id` varchar(64) NOT NULL DEFAULT '',
  `type` varchar(64) NOT NULL DEFAULT '',
  `counter` int(11) unsigned NOT NULL DEFAULT '0',
  `lastsync` datetime DEFAULT NULL,
  `pendingdata` longblob,
  PRIMARY KEY (`device_id`,`type`,`counter`),
  CONSTRAINT `syncope_synckeys::device_id--syncope_devices::id` FOREIGN KEY (`device_id`) REFERENCES `syncope_devices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `syncope_contents` (                                                                                                                                           
  `id` varchar(40) NOT NULL,                                                                                                                                                                               
  `device_id` varchar(64) DEFAULT NULL,                                                                                                                                                                    
  `class` varchar(64) DEFAULT NULL,                                                                                                                                                                        
  `contentid` varchar(64) DEFAULT NULL,                                                                                                                                                                    
  `collectionid` varchar(254) DEFAULT NULL,                                                                                                                                                                
  `creation_time` datetime DEFAULT NULL,                                                                                                                                                                   
  `is_deleted` tinyint(1) unsigned DEFAULT '0',                                                                                                                                                            
  PRIMARY KEY (`id`),                                                                                                                                                                                      
  UNIQUE KEY `device_id--class--collectionid--contentid` (`device_id`(40),`class`(40),`collectionid`(40),`contentid`(40)),                                                                                 
  KEY `acsync_contents::device_id--acsync_devices::id` (`device_id`),                                                                                                                                        
  CONSTRAINT `acsync_contents::device_id--acsync_devices::id` FOREIGN KEY (`device_id`) REFERENCES `syncope_devices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE                                         
);