<?php
/**
 * Syncroton
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class documentation
 *
 * @package     Wbxml
 * @subpackage  ActiveSync
 */
 
class Syncroton_Wbxml_Dtd_ActiveSync_CodePage21 extends Syncroton_Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 21;
    
    protected $_codePageName    = 'ComposeMail';
        
    protected $_tags = array(
        'SendMail'        => 0x05,
        'SmartForward'    => 0x06,
        'SmartReply'      => 0x07,
        'SaveInSentItems' => 0x08,
        'ReplaceMime'     => 0x09,
        'Source'          => 0x0b,
        'FolderId'        => 0x0c,
        'ItemId'          => 0x0d,
        'LongId'          => 0x0e,
        'InstanceId'      => 0x0f,
        'Mime'            => 0x10,
        'ClientId'        => 0x11,
        'Status'          => 0x12,
        'AccountId'       => 0x13,
    );
}