<?php
/**
 * Syncroton
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class documentation
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 * @todo        add missing tags
 */
 
class Syncroton_Wbxml_Dtd_ActiveSync_CodePage14 extends Syncroton_Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 14;
    
    protected $_codePageName    = 'Provision';
        
    protected $_tags = array(
        'Provision'                          => 0x05,
        'Policies'                           => 0x06,
        'Policy'                             => 0x07,
        'PolicyType'                         => 0x08,
        'PolicyKey'                          => 0x09,
        'Data'                               => 0x0a,
        'Status'                             => 0x0b,
        'RemoteWipe'                         => 0x0c,
        'EASProvisionDoc'                    => 0x0d,
        'DevicePasswordEnabled'              => 0x0e,
        'AlphanumericDevicePasswordRequired' => 0x0f,
        'RequireStorageCardEncryption'       => 0x10,
        'PasswordRecoveryEnabled'            => 0x11,
        'DocumentBrowseEnabled'              => 0x12,
        'AttachmentsEnabled'                 => 0x13,
        'MinDevicePasswordLength'            => 0x14,
        'MaxInactivityTimeDeviceLock'        => 0x15,
        'MaxDevicePasswordFailedAttempts'    => 0x16,
        'MaxAttachmentSize'                  => 0x17,
        'AllowSimpleDevicePassword'          => 0x18,
        'DevicePasswordExpiration'           => 0x19,
        'DevicePasswordHistory'              => 0x1a,
        'AllowStorageCard'                   => 0x1b,
        'AllowCamera'                        => 0x1c,
        'RequireDeviceEncryption'            => 0x1d,
        'AllowUnsignedApplications'          => 0x1e,
        'AllowUnsignedInstallationPackages'  => 0x1f,
        'MinDevicePasswordComplexCharacters' => 0x20,
        'AllowWiFi'                          => 0x21,
        'AllowTextMessaging'                 => 0x22,
        'AllowPOPIMAPEmail'                  => 0x23,
        'AllowBluetooth'                     => 0x24,
        'AllowIrDA'                          => 0x25,
        'RequireManualSyncWhenRoaming'       => 0x26,
        'AllowDesktopSync'                   => 0x27,
        'MaxCalendarAgeFilter'               => 0x28,
        'AllowHTMLEmail'                     => 0x29,
        'MaxEmailAgeFilter'                  => 0x2a,
        'MaxEmailBodyTruncationSize'         => 0x2b,
        'MaxEmailHTMLBodyTruncationSize'     => 0x2c,
        'RequireSignedSMIMEMessages'         => 0x2d,
        'RequireEncryptedSMIMEMessages'      => 0x2e,
        'RequireSignedSMIMEAlgorithm'        => 0x2F,
        'RequireEncryptionSMIMEAlgorithm'    => 0x30,
        'AllowSMIMEEncryptionAlgorithmNegotiation' => 0x31,
        'AllowSMIMESoftCerts'                => 0x32,
        'AllowBrowser'                       => 0x33,
        'AllowConsumerEmail'                 => 0x34,
        'AllowRemoteDesktop'                 => 0x35,
        'AllowInternetSharing'               => 0x36,
        'UnapprovedInROMApplicationList'     => 0x37,
        'ApplicationName'                    => 0x38,
        'ApprovedApplicationList'            => 0x39,
        'Hash'                               => 0x3a,
    );
}
