<?php
/**
 * Tine 2.0
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
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
 
class Wbxml_Dtd_ActiveSync_CodePage2 extends Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 2;
    
    protected $_codePageName    = 'Email';
        
    protected $_tags = array(     
        'Attachment'            => 0x05,
        'Attachments'           => 0x06,
        'AttName'               => 0x07,
        'AttSize'               => 0x08,
        'Att0Id'                => 0x09,
        'AttMethod'             => 0x0a,
        'AttRemoved'            => 0x0b,
        'Body'                  => 0x0c,
        'BodySize'              => 0x0d,
        'BodyTruncated'         => 0x0e,
        'DateReceived'          => 0x0f,
        'DisplayName'           => 0x10,
        'DisplayTo'             => 0x11,
        'Importance'            => 0x12,
        'MessageClass'          => 0x13,
        'Subject'               => 0x14,
        'Read'                  => 0x15,
        'To'                    => 0x16,
        'Cc'                    => 0x17,
        'From'                  => 0x18,
        'ReplyTo'               => 0x19,
/*        'Department'            => 0x1a,
        'Email1Address'         => 0x1b,
        'Email2Address'         => 0x1c,
        'Email3Address'     => 0x1d,
        'FileAs'            => 0x1e,
        'FirstName'         => 0x1f,
        'Home2PhoneNumber'  => 0x20,
        'HomeCity'          => 0x21,
        'HomeCountry'       => 0x22,
        'HomePostalCode'    => 0x23,
        'HomeState'         => 0x24,
        'HomeStreet'        => 0x25,
        'HomeFaxNumber'     => 0x26,
        'HomePhoneNumber'   => 0x27,
        'JobTitle'          => 0x28,
        'LastName'          => 0x29,
        'MiddleName'        => 0x2a,
        'MobilePhoneNumber' => 0x2b,
        'OfficeLocation'    => 0x2c,
        'OtherCity'         => 0x2d,
        'OtherCountry'      => 0x2e,
        'OtherPostalCode'   => 0x2f,
        'OtherState'        => 0x30,
        'OtherStreet'       => 0x31,
        'PagerNumber'       => 0x32,
        'RadioPhoneNumber'  => 0x33,
        'Spouse'            => 0x34,
        'Suffix'            => 0x35,
        'Title'             => 0x36,
        'WebPage'           => 0x37,
        'YomiCompanyName'   => 0x38,
        'YomiFirstName'     => 0x39, */
        'Flag'                  => 0x3a,
        'FlagStatus'            => 0x3b,
        'ContentClass'          => 0x3c,
        'FlagType'              => 0x3d,
        'CompleteTime'          => 0x3e
    );
}