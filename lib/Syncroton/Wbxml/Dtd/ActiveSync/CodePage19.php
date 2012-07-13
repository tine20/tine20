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
 
class Syncroton_Wbxml_Dtd_ActiveSync_CodePage19 extends Syncroton_Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 19;
    
    protected $_codePageName    = 'DocumentLibrary';
        
    protected $_tags = array(
        'LinkId'                => 0x05,
        'DisplayName'           => 0x06,
        'IsFolder'              => 0x07,
        'CreationDate'          => 0x08,
        'LastModifiedDate'      => 0x09,
        'IsHidden'              => 0x0a,
        'ContentLength'         => 0x0b,
        'ContentType'           => 0x0c
    );
}