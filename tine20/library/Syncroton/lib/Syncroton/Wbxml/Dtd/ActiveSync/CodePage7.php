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
 
class Syncroton_Wbxml_Dtd_ActiveSync_CodePage7 extends Syncroton_Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 7;
    
    protected $_codePageName    = 'FolderHierarchy';
        
    protected $_tags = array(     
        'Folders'                => 0x05,
        'Folder'                 => 0x06,
        'DisplayName'            => 0x07,
        'ServerId'               => 0x08,
        'ParentId'               => 0x09,
        'Type'                   => 0x0a,
        'Response'               => 0x0b,
        'Status'                 => 0x0c,
        'ContentClass'           => 0x0d,
        'Changes'                => 0x0e,
        'Add'                    => 0x0f,
        'Delete'                 => 0x10,
        'Update'                 => 0x11,
        'SyncKey'                => 0x12,
        'FolderCreate'           => 0x13,
        'FolderDelete'           => 0x14,
        'FolderUpdate'           => 0x15,
        'FolderSync'             => 0x16,
        'Count'                  => 0x17,
        'Version'                => 0x18   // not used anymore
    );
}