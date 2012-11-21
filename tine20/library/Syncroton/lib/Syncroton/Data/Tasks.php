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
    protected $_supportedFolderTypes = array(
        Syncroton_Command_FolderSync::FOLDERTYPE_TASK,
        Syncroton_Command_FolderSync::FOLDERTYPE_TASK_USER_CREATED
    );
}

