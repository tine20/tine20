<?php
/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Sync command
 *
 * @package     Model
 */

class Syncroton_Data_Calendar extends Syncroton_Data_AData implements Syncroton_Data_IDataCalendar
{
    /**
     * set attendee status for meeting
     * 
     * @param  Syncroton_Model_MeetingResponse  $request  the meeting response
     * @return  string  id of new calendar entry
     */
    public function setAttendeeStatus(Syncroton_Model_MeetingResponse $reponse)
    {
        return $reponse->requestId;
    }
    
    protected function _initData()
    {
        /**
        * used by unit tests only to simulated added folders
        */
        Syncroton_Data_AData::$folders[get_class($this)] = array(
            'calendarFolderId' => new Syncroton_Model_Folder(array(
                'id'          => sha1(mt_rand(). microtime()),
                'serverId'    => 'calendarFolderId',
                'parentId'    => 0,
                'displayName' => 'Default Contacts Folder',
                'type'        => Syncroton_Command_FolderSync::FOLDERTYPE_CALENDAR
            ))
        );
    }
}

