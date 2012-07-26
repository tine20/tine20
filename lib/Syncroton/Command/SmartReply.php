<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync SmartReply command
 *
 * @package     Syncroton
 * @subpackage  Command
 */
class Syncroton_Command_SmartReply extends Syncroton_Command_SendMail
{
    protected $_itemId;
    
    protected $_collectionId;
        
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @todo can we get rid of LIBXML_NOWARNING
     * @todo we need to stored the initial data for folders and lifetime as the phone is sending them only when they change
     * @return resource
     */
    public function handle()
    {
        parent::handle();
        
        $this->_collectionId    = $_GET['CollectionId'];
        $this->_itemId          = $_GET['ItemId'];
        
        if ($this->_logger instanceof Zend_Log)
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " collectionId: " . $this->_collectionId . " itemId: " . $this->_itemId);
        
    }
    
    /**
     * this function generates the response for the client
     * 
     * @return void
     */
    public function getResponse()
    {
        $dataController = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_EMAIL, $this->_device, $this->_syncTimeStamp);
    
        $dataController->replyEmail($this->_collectionId, $this->_itemId, $this->_inputStream, $this->_saveInSent);
    }
}
