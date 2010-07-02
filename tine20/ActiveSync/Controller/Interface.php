<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 */
 
interface ActiveSync_Controller_Interface
{
    /**
     * delete entry
     *
     * @param  string  $_collectionId
     * @param  string  $_id
     * @param  array   $_options
     */
    public function delete($_folderId, $_id, $_options);
    
    /**
     * move item from one folder to another
     * 
     * @param  string  $_srcFolder
     * @param  string  $_srcItem
     * @param  string  $_dstFolder
     * @return string  the new item id
     */
    public function moveItem($_srcFolder, $_srcItem, $_dstFolder);
}