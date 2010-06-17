<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
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
 */
class ActiveSync_Command_MoveItems extends ActiveSync_Command_Wbxml 
{        
    const STATUS_INVALID_SOURCE         = 1;
    const STATUS_INVALID_DESTINATION    = 2;
    const STATUS_SUCCESS                = 3;
    
    protected $_defaultNameSpace    = 'uri:Move';
    protected $_documentElement     = 'Moves';
    
    /**
     * instance of ActiveSync_Controller
     *
     * @var ActiveSync_Controller
     */
    #protected $_controller;
    
    /**
     * list of items to move
     * 
     * @var array
     */
    protected $_moves = array();
    
    /**
     * the constructor
     *
     * @param ActiveSync_Model_Device $_device
     */
    #public function __construct(ActiveSync_Model_Device $_device)
    #{
    #    parent::__construct($_device);
    #    
    #    #$this->_controller           = ActiveSync_Controller::getInstance();
    #
    #}
    
    /**
     * parse MoveItems request
     *
     */
    public function handle()
    {
        $xml = simplexml_import_dom($this->_inputDom);
        
        foreach ($xml->Move as $move) {
            $this->_moves[] = array(
                'srcMsgId' => (string)$move->SrcMsgId,
                'srcFldId' => (string)$move->SrcFldId,
                'dstFldId' => (string)$move->DstFldId            
            );
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " moves: " . print_r($this->_moves, true));        
    }
    
    /**
     * generate MoveItems response
     * 
     */
    public function getResponse()
    {
        $folderStateBackend   = new ActiveSync_Backend_FolderState();
        
        $moves = $this->_outputDom->documentElement;
        
        foreach ($this->_moves as $move) {
            $response = $moves->appendChild($this->_outputDom->createElementNS('uri:Move', 'Response'));
            $response->appendChild($this->_outputDom->createElementNS('uri:Move', 'SrcMsgId', $move['srcMsgId']));
            
            try {
                $folder         = $folderStateBackend->getByProperty($move['srcFldId'], 'folderid');
                $dataController = ActiveSync_Controller::dataFactory($folder->class, $this->_device, $this->_syncTimeStamp);
                $newId          = $dataController->moveItem($move['srcFldId'], $move['srcMsgId'], $move['dstFldId']);
                
                $response->appendChild($this->_outputDom->createElementNS('uri:Move', 'Status', ActiveSync_Command_MoveItems::STATUS_SUCCESS));
                $response->appendChild($this->_outputDom->createElementNS('uri:Move', 'DstMsgId', $newId));
            } catch (Tinebase_Exception_NotFound $e) {
                $response->appendChild($this->_outputDom->createElementNS('uri:Move', 'Status', ActiveSync_Command_MoveItems::STATUS_INVALID_SOURCE));
            } catch (Exception $e) {
                $response->appendChild($this->_outputDom->createElementNS('uri:Move', 'Status', ActiveSync_Command_MoveItems::STATUS_INVALID_DESTINATION));
            }
        }
        
        parent::getResponse();
    }
}