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
 
class Wbxml_Dtd_ActiveSync_CodePage17 extends Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 17;
    
    protected $_codePageName    = 'AirSyncBase';
    
    protected $_tags = array(     
        'BodyPreference'    => 0x05,
        'Type'              => 0x06,
        'TruncationSize'    => 0x07,
        'AllOrNone'         => 0x08,
        'Body'              => 0x0a,
        'Data'              => 0x0b,
        'EstimatedDataSize' => 0x0c,
        'Truncated'         => 0x0d,
        'Attachments'       => 0x0e,
        'Attachment'        => 0x0f,
        'DisplayName'       => 0x10,
        'FileReference'     => 0x11,
        'Method'            => 0x12,
        'ContentId'         => 0x13,
        'ContentLocation'   => 0x14,
        'IsInline'          => 0x15,
        'NativeBodyType'    => 0x16,
        'ContentType'       => 0x17
    );    
}