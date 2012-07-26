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
interface Syncroton_Backend_IContent
{
    /**
     * create new content state
     *
     * @param Syncroton_Model_IContent $_contentState
     * @return Syncroton_Model_IContent
     */
    public function create(Syncroton_Model_IContent $_contentState);
        
    /**
     * mark state as deleted. The state gets removed finally, 
     * when the synckey gets validated during next sync.
     * 
     * @param Syncroton_Model_IContent|string $_id
     */
    public function delete($_id);
    
    /**
     * @param Syncroton_Model_IDevice|string $_deviceId
     * @param Syncroton_Model_IFolder|string $_folderId
     * @param string $_contentId
     * @return Syncroton_Model_IContent
     */
    public function getContentState($_deviceId, $_folderId, $_contentId);
}
