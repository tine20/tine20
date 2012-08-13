<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Backend
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * sql backend class for the folder state
 *
 * @package     Syncroton
 * @subpackage  Backend
 */
interface Syncroton_Backend_IContent extends Syncroton_Backend_IBackend
{
    /**
     * @param Syncroton_Model_IDevice|string $_deviceId
     * @param Syncroton_Model_IFolder|string $_folderId
     * @param string $_contentId
     * @return Syncroton_Model_IContent
     */
    public function getContentState($_deviceId, $_folderId, $_contentId);

    /**
     * get array of ids which got send to the client for a given class
     *
     * @param Syncroton_Model_IDevice|string $_deviceId
     * @param Syncroton_Model_IFolder|string $_folderId
     * @return array
     */
    public function getFolderState($_deviceId, $_folderId);

    /**
     * reset list of stored id
     *
     * @param Syncroton_Model_IDevice|string $_deviceId
     * @param Syncroton_Model_IFolder|string $_folderId
     */
    public function resetState($_deviceId, $_folderId);
}
