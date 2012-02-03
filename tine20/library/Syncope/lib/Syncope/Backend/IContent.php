<?php
/**
 * Tine 2.0
 *
 * @package     Syncope
 * @subpackage  Backend
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * sql backend class for the folder state
 *
 * @package     Syncope
 * @subpackage  Backend
 */
interface Syncope_Backend_IContent
{
    /**
     * create new content state
     *
     * @param Syncope_Model_IContent $_contentState
     * @return Syncope_Model_IContent
     */
    public function create(Syncope_Model_IContent $_contentState);
        
    /**
     * mark state as deleted. The state gets removed finally, 
     * when the synckey gets validated during next sync.
     * 
     * @param Syncope_Model_IContent|string $_id
     */
    public function delete($_id);
    
    /**
     * @param Syncope_Model_IDevice|string $_deviceId
     * @param Syncope_Model_IFolder|string $_folderId
     * @param string $_contentId
     * @return Syncope_Model_IContent
     */
    public function getContentState($_deviceId, $_folderId, $_contentId);
}
