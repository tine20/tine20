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
 
class Syncroton_Wbxml_Dtd_ActiveSync_CodePage16 extends Syncroton_Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 16;
    
    protected $_codePageName    = 'GAL';
        
    protected $_tags = array(
        'DisplayName'           => 0x05,
        'Phone'                 => 0x06,
        'Office'                => 0x07,
        'Title'                 => 0x08,
        'Company'               => 0x09,
        'Alias'                 => 0x0a,
        'FirstName'             => 0x0b,
        'LastName'              => 0x0c,
        'HomePhone'             => 0x0d,
        'MobilePhone'           => 0x0e,
        'EmailAddress'          => 0x0f,
        'Picture'               => 0x10,
        'Status'                => 0x11,
        'Data'                  => 0x12,
    );
}