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
 
class Syncroton_Wbxml_Dtd_ActiveSync_CodePage6 extends Syncroton_Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 6;
    
    protected $_codePageName    = 'ItemEstimate';
        
    protected $_tags = array(     
        'GetItemEstimate'         => 0x05,
        'Version'                 => 0x06,
        'Collections'             => 0x07,
        'Collection'              => 0x08,
        'Class'                   => 0x09,
        'CollectionId'            => 0x0a,
        'DateTime'                => 0x0b,
        'Estimate'                => 0x0c,
        'Response'                => 0x0d,
        'Status'                  => 0x0e
    );
}