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
 
class Syncroton_Wbxml_Dtd_ActiveSync_CodePage8 extends Syncroton_Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 8;
    
    protected $_codePageName    = 'MeetingResponse';
        
    protected $_tags = array(     
        'CalendarId'              => 0x05,
        'CollectionId'            => 0x06,
        'MeetingResponse'         => 0x07,
        'RequestId'               => 0x08,
        'Request'                 => 0x09,
        'Result'                  => 0x0a,
        'Status'                  => 0x0b,
        'UserResponse'            => 0x0c,
        'Version'                 => 0x0d, // not used anymore
        'InstanceId'              => 0x0e
    );
}