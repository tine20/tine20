CREATE TABLE IF NOT EXISTS `Syncroton_policy` (
    `id` varchar(40) NOT NULL,
    `name` varchar(255) NOT NULL,
    `description` varchar(255) DEFAULT NULL,
    `policy_key` varchar(64) NOT NULL,
    `json_policy` blob NOT NULL,
    PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `Syncroton_device` (
    `id` varchar(40) NOT NULL,
    `deviceid` varchar(64) NOT NULL,
    `devicetype` varchar(64) NOT NULL,
    `owner_id` varchar(40) NOT NULL,
    `acsversion` varchar(40) NOT NULL,
    `policykey` varchar(64) DEFAULT NULL,
    `policy_id` varchar(40) DEFAULT NULL,
    `useragent` varchar(255) DEFAULT NULL,
    `imei` varchar(255) DEFAULT NULL,
    `model` varchar(255) DEFAULT NULL,
    `friendlyname` varchar(255) DEFAULT NULL,
    `os` varchar(255) DEFAULT NULL,
    `oslanguage` varchar(255) DEFAULT NULL,
    `phonenumber` varchar(255) DEFAULT NULL,
    `pinglifetime` int(11) DEFAULT NULL,
    `remotewipe` int(11) DEFAULT '0',
    `pingfolder` longblob,
    `lastsynccollection` longblob DEFAULT NULL,
    `lastping` datetime DEFAULT NULL,
    'contactsfilter_id' varchar(40) DEFAULT NULL,
    'calendarfilter_id' varchar(40) DEFAULT NULL,
    'tasksfilter_id' varchar(40) DEFAULT NULL,
    'emailfilter_id' varchar(40) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `owner_id--deviceid` (`owner_id`, `deviceid`)
);

CREATE TABLE IF NOT EXISTS `Syncroton_folder` (
    `id` varchar(40) NOT NULL,
    `device_id` varchar(40) NOT NULL,
    `class` varchar(64) NOT NULL,
    `folderid` varchar(254) NOT NULL,
    `parentid` varchar(254) DEFAULT NULL,
    `displayname` varchar(254) NOT NULL,
    `type` int(11) NOT NULL,
    `creation_time` datetime NOT NULL,
    `lastfiltertype` int(11) DEFAULT NULL,
    `supportedfields` longblob DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `device_id--class--folderid` (`device_id`(40),`class`(40),`folderid`(40)),
    KEY `folderstates::device_id--devices::id` (`device_id`),
    CONSTRAINT `folderstates::device_id--devices::id` FOREIGN KEY (`device_id`) REFERENCES `Syncroton_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE 
);

CREATE TABLE IF NOT EXISTS `Syncroton_synckey` (
    `id` varchar(40) NOT NULL,
    `device_id` varchar(40) NOT NULL DEFAULT '',
    `type` varchar(64) NOT NULL DEFAULT '',
    `counter` int(11) NOT NULL DEFAULT '0',
    `lastsync` datetime DEFAULT NULL,
    `pendingdata` longblob,
    PRIMARY KEY (`id`),
    UNIQUE KEY `device_id--type--counter` (`device_id`,`type`,`counter`),
    CONSTRAINT `Syncroton_synckey::device_id--Syncroton_device::id` FOREIGN KEY (`device_id`) REFERENCES `Syncroton_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `Syncroton_content` (
    `id` varchar(40) NOT NULL,
    `device_id` varchar(40) DEFAULT NULL,
    `folder_id` varchar(40) DEFAULT NULL,
    `contentid` varchar(64) DEFAULT NULL,
    `creation_time` datetime DEFAULT NULL,
    `creation_synckey` int(11) NOT NULL,
    `is_deleted` tinyint(1) DEFAULT '0',
    PRIMARY KEY (`id`),
    UNIQUE KEY `device_id--folder_id--contentid` (`device_id`(40),`folder_id`(40),`contentid`(40)),
    KEY `Syncroton_contents::device_id` (`device_id`),
    CONSTRAINT `Syncroton_contents::device_id--Syncroton_device::id` FOREIGN KEY (`device_id`) REFERENCES `Syncroton_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `Syncroton_data` (
    `id` varchar(40) NOT NULL,
    `class` varchar(40) NOT NULL,
    `folder_id` varchar(40) NOT NULL,
    `data` longblob,
    PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `Syncroton_data_folder` (
    `id` varchar(40) NOT NULL,
    `owner_id` varchar(40) NOT NULL,
    `type` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `parent_id` varchar(40) DEFAULT NULL,
    `creation_time` datetime NOT NULL,
    `last_modified_time` datetime DEFAULT NULL,
    PRIMARY KEY (`id`, `owner_id`)
);
