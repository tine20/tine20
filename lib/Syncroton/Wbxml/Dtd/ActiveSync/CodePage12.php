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
 
class Syncroton_Wbxml_Dtd_ActiveSync_CodePage12 extends Syncroton_Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 12;
    
    protected $_codePageName    = 'Contacts2';
        
    protected $_tags = array(     
        'CustomerId'              => 0x05,
        'GovernmentId'            => 0x06,
        'IMAddress'               => 0x07,
        'IMAddress2'              => 0x08,
        'IMAddress3'              => 0x09,
        'ManagerName'             => 0x0a,
        'CompanyMainPhone'        => 0x0b,
        'AccountName'             => 0x0c,
        'NickName'                => 0x0d,
        'MMS'                     => 0x0e
    );
}