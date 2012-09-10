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
 
class Syncroton_Wbxml_Dtd_ActiveSync_CodePage1 extends Syncroton_Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 1;
    
    protected $_codePageName    = 'Contacts';
        
    protected $_tags = array(     
        'Anniversary'               => 0x05,
        'AssistantName'             => 0x06,
        'AssistantPhoneNumber'      => 0x07,
        'Birthday'                  => 0x08,
        'Body'                      => 0x09,
        'BodySize'                  => 0x0a,
        'BodyTruncated'             => 0x0b,
        'Business2PhoneNumber'      => 0x0c,
        'BusinessAddressCity'       => 0x0d,
        'BusinessAddressCountry'    => 0x0e,
        'BusinessAddressPostalCode' => 0x0f,
        'BusinessAddressState'   => 0x10,
        'BusinessAddressStreet'  => 0x11,
        'BusinessFaxNumber'      => 0x12,
        'BusinessPhoneNumber'    => 0x13,
        'CarPhoneNumber'         => 0x14,
        'Categories'             => 0x15,
        'Category'               => 0x16,
        'Children'               => 0x17,
        'Child'                  => 0x18,
        'CompanyName'            => 0x19,
        'Department'             => 0x1a,
        'Email1Address'          => 0x1b,
        'Email2Address'          => 0x1c,
        'Email3Address'          => 0x1d,
        'FileAs'                 => 0x1e,
        'FirstName'              => 0x1f,
        'Home2PhoneNumber'       => 0x20,
        'HomeAddressCity'        => 0x21,
        'HomeAddressCountry'     => 0x22,
        'HomeAddressPostalCode'  => 0x23,
        'HomeAddressState'       => 0x24,
        'HomeAddressStreet'      => 0x25,
        'HomeFaxNumber'          => 0x26,
        'HomePhoneNumber'        => 0x27,
        'JobTitle'               => 0x28,
        'LastName'               => 0x29,
        'MiddleName'             => 0x2a,
        'MobilePhoneNumber'      => 0x2b,
        'OfficeLocation'         => 0x2c,
        'OtherAddressCity'       => 0x2d,
        'OtherAddressCountry'    => 0x2e,
        'OtherAddressPostalCode' => 0x2f,
        'OtherAddressState'      => 0x30,
        'OtherAddressStreet'     => 0x31,
        'PagerNumber'            => 0x32,
        'RadioPhoneNumber'       => 0x33,
        'Spouse'                 => 0x34,
        'Suffix'                 => 0x35,
        'Title'                  => 0x36,
        'WebPage'                => 0x37,
        'YomiCompanyName'        => 0x38,
        'YomiFirstName'          => 0x39,
        'YomiLastName'           => 0x3a,
        'Rtf'                    => 0x3b,
        'Picture'                => 0x3c,
        'Alias'                  => 0x3d,
        'WeightedRank'           => 0x3e
    );
}