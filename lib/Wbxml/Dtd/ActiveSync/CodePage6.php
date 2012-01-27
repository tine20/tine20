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
 
class Wbxml_Dtd_ActiveSync_CodePage6 extends Wbxml_Dtd_ActiveSync_Abstract
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