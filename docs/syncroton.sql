CREATE TABLE IF NOT EXISTS `syncroton_policy` (
    `id` varchar(40) NOT NULL,
    `name` varchar(255) NOT NULL,
    `description` varchar(255) DEFAULT NULL,
    `policy_key` varchar(64) NOT NULL,
    `allow_bluetooth` int(11) DEFAULT NULL,
    `allow_browser` int(11) DEFAULT NULL,
    `allow_camera` int(11) DEFAULT NULL,
    `allow_consumer_email` int(11) DEFAULT NULL,
    `allow_desktop_sync` int(11) DEFAULT NULL,
    `allow_h_t_m_l_email` int(11) DEFAULT NULL,
    `allow_internet_sharing` int(11) DEFAULT NULL,
    `allow_ir_d_a` int(11) DEFAULT NULL,
    `allow_p_o_p_i_m_a_p_email` int(11) DEFAULT NULL,
    `allow_remote_desktop` int(11) DEFAULT NULL,
    `allow_simple_device_password` int(11) DEFAULT NULL,
    `allow_s_m_i_m_e_encryption_algorithm_negotiation` int(11) DEFAULT NULL,
    `allow_s_m_i_m_e_soft_certs` int(11) DEFAULT NULL,
    `allow_storage_card` int(11) DEFAULT NULL,
    `allow_text_messaging` int(11) DEFAULT NULL,
    `allow_unsigned_applications` int(11) DEFAULT NULL,
    `allow_unsigned_installation_packages` int(11) DEFAULT NULL,
    `allow_wifi` int(11) DEFAULT NULL,
    `alphanumeric_device_password_required` int(11) DEFAULT NULL,
    `approved_application_list` varchar(255) DEFAULT NULL,
    `attachments_enabled` int(11) DEFAULT NULL,
    `device_password_enabled` int(11) DEFAULT NULL,
    `device_password_expiration` int(11) DEFAULT NULL,
    `device_password_history` int(11) DEFAULT NULL,
    `max_attachment_size` int(11) DEFAULT NULL,
    `max_calendar_age_filter` int(11) DEFAULT NULL,
    `max_device_password_failed_attempts` int(11) DEFAULT NULL,
    `max_email_age_filter` int(11) DEFAULT NULL,
    `max_email_body_truncation_size` int(11) DEFAULT NULL,
    `max_email_h_t_m_l_body_truncation_size` int(11) DEFAULT NULL,
    `max_inactivity_time_device_lock` int(11) DEFAULT NULL,
    `min_device_password_complex_characters` int(11) DEFAULT NULL,
    `min_device_password_length` int(11) DEFAULT NULL,
    `password_recovery_enabled` int(11) DEFAULT NULL,
    `require_device_encryption` int(11) DEFAULT NULL,
    `require_encrypted_s_m_i_m_e_messages` int(11) DEFAULT NULL,
    `require_encryption_s_m_i_m_e_algorithm` int(11) DEFAULT NULL,
    `require_manual_sync_when_roaming` int(11) DEFAULT NULL,
    `require_signed_s_m_i_m_e_algorithm` int(11) DEFAULT NULL,
    `require_signed_s_m_i_m_e_messages` int(11) DEFAULT NULL,
    `require_storage_card_encryption` int(11) DEFAULT NULL,
    `unapproved_in_r_o_m_application_list` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `syncroton_device` (
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
    'contactsfilter_id' varchar(40) DEFAULT NULL,
    'calendarfilter_id' varchar(40) DEFAULT NULL,
    'tasksfilter_id' varchar(40) DEFAULT NULL,
    'emailfilter_id' varchar(40) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `owner_id--deviceid` (`owner_id`, `deviceid`)
);

CREATE TABLE IF NOT EXISTS `syncroton_folder` (
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
    CONSTRAINT `folderstates::device_id--devices::id` FOREIGN KEY (`device_id`) REFERENCES `syncroton_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE 
);

CREATE TABLE IF NOT EXISTS `syncroton_synckey` (
    `id` varchar(40) NOT NULL,
    `device_id` varchar(40) NOT NULL DEFAULT '',
    `type` varchar(64) NOT NULL DEFAULT '',
    `counter` int(11) NOT NULL DEFAULT '0',
    `lastsync` datetime DEFAULT NULL,
    `pendingdata` longblob,
    PRIMARY KEY (`id`),
    UNIQUE KEY `device_id--type--counter` (`device_id`,`type`,`counter`),
    CONSTRAINT `syncroton_synckey::device_id--syncroton_device::id` FOREIGN KEY (`device_id`) REFERENCES `syncroton_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `syncroton_content` (
    `id` varchar(40) NOT NULL,
    `device_id` varchar(40) DEFAULT NULL,
    `folder_id` varchar(40) DEFAULT NULL,
    `contentid` varchar(64) DEFAULT NULL,
    `creation_time` datetime DEFAULT NULL,
    `creation_synckey` int(11) NOT NULL,
    `is_deleted` tinyint(1) DEFAULT '0',
    PRIMARY KEY (`id`),
    UNIQUE KEY `device_id--folder_id--contentid` (`device_id`(40),`folder_id`(40),`contentid`(40)),
    KEY `syncroton_contents::device_id` (`device_id`),
    CONSTRAINT `syncroton_contents::device_id--syncroton_device::id` FOREIGN KEY (`device_id`) REFERENCES `syncroton_device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `syncroton_data` (
    `id` varchar(40) NOT NULL,
    `class` varchar(40) NOT NULL,
    `folder_id` varchar(40) NOT NULL,
    `data` longblob,
    PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `syncroton_data_folder` (
    `id` varchar(40) NOT NULL,
    `owner_id` varchar(40) NOT NULL,
    `type` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `parent_id` varchar(40) DEFAULT NULL,
    `creation_time` datetime NOT NULL,
    `last_modified_time` datetime DEFAULT NULL,
    PRIMARY KEY (`id`, `owner_id`)
);
