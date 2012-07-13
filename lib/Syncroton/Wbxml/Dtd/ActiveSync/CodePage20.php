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
 
class Syncroton_Wbxml_Dtd_ActiveSync_CodePage20 extends Syncroton_Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 20;
    
    protected $_codePageName    = 'ItemOperations';
        
    protected $_tags = array(     
        'ItemOperations'        => 0x05,
        'Fetch'                 => 0x06,
        'Store'                 => 0x07,
        'Options'               => 0x08,
        'Range'                 => 0x09,
        'Total'                 => 0x0a,
        'Properties'            => 0x0b,
        'Data'                  => 0x0c,
        'Status'                => 0x0d,
        'Response'              => 0x0e,
        'Version'               => 0x0f,
        'Schema'                => 0x10,
        'Part'                  => 0x11,
        'EmptyFolderContents'   => 0x12,
        'DeleteSubFolders'      => 0x13,
        'UserName'              => 0x14,
        'Password'              => 0x15,
        'Move'                  => 0x16,
        'DstFldId'              => 0x17,
        'ConversationId'        => 0x18,
        'MoveAlways'            => 0x19,
    );
}