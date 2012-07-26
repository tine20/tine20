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

class Syncroton_Data_Tasks extends Syncroton_Data_AData
{
    protected function _initData()
    {
        /**
        * used by unit tests only to simulated added folders
        */
        Syncroton_Data_AData::$folders[get_class($this)] = array(
            'tasksFolderId' => new Syncroton_Model_Folder(array(
                'id'          => sha1(mt_rand(). microtime()),
                'folderid'    => 'tasksFolderId',
                'parentid'    => null,
                'displayname' => 'Default Tasks Folder',
                'type'        => Syncroton_Command_FolderSync::FOLDERTYPE_TASK
            ))
        );
        
        /**
         * used by unit tests only to simulated added folders
         */
        Syncroton_Data_AData::$entries[get_class($this)] = array(
            'tasksFolderId' => array(
            )
        );
    }
}

