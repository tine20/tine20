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
 
class Wbxml_Dtd_ActiveSync_CodePage0 extends Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 0;
    
    protected $_codePageName    = 'AirSync';
    
    protected $_tags = array(     
        'Sync'              => 0x05,
        'Responses'         => 0x06,
        'Add'               => 0x07,
        'Change'            => 0x08,
        'Delete'            => 0x09,
        'Fetch'             => 0x0a,
        'SyncKey'           => 0x0b,
        'ClientId'          => 0x0c,
        'ServerId'          => 0x0d,
        'Status'            => 0x0e,
        'Collection'        => 0x0f,
        'Class'             => 0x10,
        'Version'           => 0x11,
        'CollectionId'      => 0x12,
        'GetChanges'        => 0x13,
        'MoreAvailable'     => 0x14,
        'WindowSize'        => 0x15,
        'Commands'          => 0x16,
        'Options'           => 0x17,
        'FilterType'        => 0x18,
        'Truncation'        => 0x19,
        'RtfTruncation'     => 0x1a,
        'Conflict'          => 0x1b,
        'Collections'       => 0x1c,
        'ApplicationData'   => 0x1d,
        'DeletesAsMoves'    => 0x1e,
        'NotifyGUID'        => 0x1f,
        'Supported'         => 0x20,
        'SoftDelete'        => 0x21,
        'MIMESupport'       => 0x22,
        'MIMETruncation'    => 0x23,
        'Wait'              => 0x24,
        'Limit'             => 0x25,
        'Partial'           => 0x26
    );
}