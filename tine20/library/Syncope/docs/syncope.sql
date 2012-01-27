CREATE TABLE IF NOT EXISTS `syncope_device` (
    `id` varchar(40) NOT NULL, `name`,
    `deviceid` varchar(64) NOT NULL,                                                                                                                                                                         
    `devicetype` varchar(64) NOT NULL,
    `policykey` varchar(64) DEFAULT NULL,
    `policy_id` varchar(40) NOT NULL,
    `owner_id` varchar(40) NOT NULL,
    `useragent` varchar(255) NOT NULL,
    `acsversion` varchar(40) NOT NULL,
    `pinglifetime` int(11) DEFAULT NULL,
    `remotewipe` int(11) DEFAULT '0',
    `pingfolder` longblob,
    PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `syncope_folder` (
  `id` varchar(40) NOT NULL,
  `device_id` varchar(40) NOT NULL,
  `class` varchar(64) NOT NULL,
  `folderid` varchar(254) NOT NULL,
  `parentid` varchar(254) DEFAULT NULL,
  `displayname` varchar(254) NOT NULL,
  `type` int(11) NOT NULL,
  `creation_time` datetime NOT NULL,
  `lastfiltertype` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_id--class--folderid` (`device_id`(40),`class`(40),`folderid`(40)),
  KEY `folderstates::device_id--devices::id` (`device_id`),
  CONSTRAINT `folderstates::device_id--devices::id` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE 
);

CREATE TABLE `syncope_synckey` (
  `id` varchar(40) NOT NULL,
  `device_id` varchar(40) NOT NULL DEFAULT '',
  `type` varchar(64) NOT NULL DEFAULT '',
  `counter` int(11) unsigned NOT NULL DEFAULT '0',
  `lastsync` datetime DEFAULT NULL,
  `pendingdata` longblob,
  PRIMARY KEY (`device_id`,`type`,`counter`),
  CONSTRAINT `syncope_synckey::device_id--syncope_device::id` FOREIGN KEY (`device_id`) REFERENCES `syncope_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `syncope_content` (                                                                                                                                           
  `id` varchar(40) NOT NULL,                                                                                                                                                                               
  `device_id` varchar(40) DEFAULT NULL,                                                                                                                                                                    
  `folder_id` varchar(40) DEFAULT NULL,                                                                                                                                                                        
  `contentid` varchar(64) DEFAULT NULL,                                                                                                                                                                    
  `creation_time` datetime DEFAULT NULL,                                                                                                                                                                   
  `is_deleted` tinyint(1) unsigned DEFAULT '0',                                                                                                                                                            
  PRIMARY KEY (`id`),                                                                                                                                                                                      
  UNIQUE KEY `device_id--folder_id--contentid` (`device_id`(40),`folder_id`(40),`contentid`(40)),                                                                                 
  KEY `acsync_contents::device_id--acsync_device::id` (`device_id`),                                                                                                                                        
  CONSTRAINT `acsync_contents::device_id--acsync_device::id` FOREIGN KEY (`device_id`) REFERENCES `syncope_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE                                         
);