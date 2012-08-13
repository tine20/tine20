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
    );
}