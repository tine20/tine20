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
 
class Syncroton_Wbxml_Dtd_ActiveSync_CodePage22 extends Syncroton_Wbxml_Dtd_ActiveSync_Abstract
{
    protected $_codePageNumber  = 22;
    
    protected $_codePageName    = 'Email2';
        
    protected $_tags = array(
        'UmCallerID'            => 0x05,
        'UmUserNotes'           => 0x06,
        'UmAttDuration'         => 0x07,
        'UmAttOrder'            => 0x08,
        'ConversationId'        => 0x09,
        'ConversationIndex'     => 0x0a,
        'LastVerbExecuted'      => 0x0b,
        'LastVerbExecutionTime' => 0x0c,
        'ReceivedAsBcc'         => 0x0d,
        'Sender'                => 0x0e,
        'CalendarType'          => 0x0f,
        'IsLeapMonth'           => 0x10,
        'AccountId'             => 0x11,
        'FirstDayOfWeek'        => 0x12,
        'MeetingMessageType'    => 0x13,
   );
}