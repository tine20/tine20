<?php

/**
 * Syncope
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL),
 *              Version 1, the distribution of the Tine 2.0 Syncope module in or to the
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Sync command
 *
 * @package     Model
 */

class Syncope_Data_Email implements Syncope_Data_IData
{
    public function getAllFolders()
    {
        return array(
            'emailInboxFolderId' => array(
                'folderId'    => 'emailInboxFolderId',
                'parentId'    => null,
                'displayName' => 'Inbox',
                'type'        => Syncope_Command_FolderSync::FOLDERTYPE_INBOX
            ),
            'emailSentFolderId' => array(
                'folderId'    => 'emailSentFolderId',
                'parentId'    => null,
                'displayName' => 'Sent',
                'type'        => Syncope_Command_FolderSync::FOLDERTYPE_SENTMAIL
            )
        );
    }
}

