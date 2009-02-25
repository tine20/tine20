<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.html AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 */
 
abstract class ActiveSync_Controller_Abstract
{
    /**
     * timestamp to use for all sync requests
     *
     * @var Zend_Date
     */
    protected $_syncTimeStamp;
    
    public function __construct(Zend_Date $_syncTimeStamp)
    {
        $this->_syncTimeStamp = $_syncTimeStamp;
    }
    
    public function getFolders()
    {
        return $this->_folders;
    }
    
    public function getFolder($_folderId)
    {
        foreach($this->_folders as $folder) {
            if($folder['folderId'] == $_folderId) {
                return $folder;
            }
        }
        
        throw new ActiveSync_Exception_FolderNotFound('folder not found. ' . $_folderId);
    }
    
    abstract public function getItemEstimate($_startTimeStamp = NULL, $_endTimeStamp = NULL);
    
    abstract public function add($_collectionId, SimpleXMLElement $_data);

    abstract public function change($_collectionId, $_id, SimpleXMLElement $_data);
    
    abstract public function delete($_collectionId, $_id);
    
    abstract public function getSince($_field, $_startTimeStamp, $_endTimeStamp);
    
    abstract public function appendXML(DOMDocument $_xmlDocument, DOMElement $_xmlNode, $_serverId);
}