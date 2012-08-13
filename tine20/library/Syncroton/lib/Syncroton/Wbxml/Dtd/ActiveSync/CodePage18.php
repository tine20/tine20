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
 */
 
class Syncroton_Wbxml_Dtd_ActiveSync_CodePage18 extends Syncroton_Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 18;
    
    protected $_codePageName    = 'Settings';
        
    protected $_tags = array(     
        'Settings'                  => 0x05,
        'Status'                    => 0x06,
        'Get'                       => 0x07,
        'Set'                       => 0x08,
        'Oof'                       => 0x09,
        'OofState'                  => 0x0a,
        'StartTime'                 => 0x0b,
        'EndTime'                   => 0x0c,
        'OofMessage'                => 0x0d,
        'AppliesToInternal'         => 0x0e,
        'AppliesToExternalKnow'     => 0x0f,
        'AppliesToExternalUnknown'  => 0x10,
        'Enabled'                   => 0x11,
        'ReplyMessage'              => 0x12,
        'BodyType'                  => 0x13,
        'DevicePassword'            => 0x14,
        'Password'                  => 0x15,
        'DeviceInformation'         => 0x16,
        'Model'                     => 0x17,
        'IMEI'                      => 0x18,
        'FriendlyName'              => 0x19,
        'OS'                        => 0x1a,
        'OSLanguage'                => 0x1b,
        'PhoneNumber'               => 0x1c,
        'UserInformation'           => 0x1d,
        'EmailAddresses'            => 0x1e,
        'SmtpAddress'               => 0x1f,
        'UserAgent'                 => 0x20,
        'EnableOutboundSMS'         => 0x21,
        'MobileOperator'            => 0x22,
        'PrimarySmtpAddress'        => 0x23,
        'Accounts'                  => 0x24,
        'Account'                   => 0x25,
        'AccountId'                 => 0x26,
        'AccountName'               => 0x27,
        'UserDisplayName'           => 0x28,
        'SendDisabled'              => 0x29,
        'RightsManagementInformation' => 0x2b,
    );
}