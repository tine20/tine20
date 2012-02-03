<?php
/**
 * Tine 2.0
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id:AirSync.php 4968 2008-10-17 09:09:33Z l.kneschke@metaways.de $
 */

/**
 * class documentation
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 */
 
class Wbxml_Dtd_ActiveSync_CodePage18 extends Wbxml_Dtd_ActiveSync_Abstract
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
        'SmtpAddress'               => 0x1f
    );
}