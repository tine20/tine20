<?php
/**
 * Syncope
 *
 * @package     Syncope
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync MoveItem command
 *
 * @package     Syncope
 * @subpackage  Command
 */
class Syncope_Command_MoveItems extends Syncope_Command_Wbxml 
{        
    const STATUS_INVALID_SOURCE         = 1;
    const STATUS_INVALID_DESTINATION    = 2;
    const STATUS_SUCCESS                = 3;
    
    protected $_defaultNameSpace    = 'uri:Move';
    protected $_documentElement     = 'Moves';
    
    /**
     * list of items to move
     * 
     * @var array
     */
    protected $_moves = array();
    
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
        
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " moves: " . print_r($this->_moves, true));        
    }
    
    /**
     * generate MoveItems response
     */
    public function getResponse()
    {
        $moves = $this->_outputDom->documentElement;
        
        foreach ($this->_moves as $move) {
            $response = $moves->appendChild($this->_outputDom->createElementNS('uri:Move', 'Response'));
            $response->appendChild($this->_outputDom->createElementNS('uri:Move', 'SrcMsgId', $move['srcMsgId']));
            
            try {
                $sourceFolder      = $this->_folderBackend->getFolder($this->_device, $move['srcFldId']);
            } catch (Syncope_Exception_NotFound $senf) {
                $sourceFolder = null;
            }
            
            try {
                $destinationFolder = $this->_folderBackend->getFolder($this->_device, $move['dstFldId']);
            } catch (Syncope_Exception_NotFound $senf) {
                $destinationFolder = null;
            }
                
            if ($sourceFolder === null) {
                $response->appendChild($this->_outputDom->createElementNS('uri:Move', 'Status', Syncope_Command_MoveItems::STATUS_INVALID_SOURCE));
            } else if ($destinationFolder === null) {
                $response->appendChild($this->_outputDom->createElementNS('uri:Move', 'Status', Syncope_Command_MoveItems::STATUS_INVALID_DESTINATION));
            } else {
                $dataController    = Syncope_Data_Factory::factory($sourceFolder->class, $this->_device, $this->_syncTimeStamp);
                
                $newId             = $dataController->moveItem($move['srcFldId'], $move['srcMsgId'], $move['dstFldId']);
                
                $response->appendChild($this->_outputDom->createElementNS('uri:Move', 'Status', Syncope_Command_MoveItems::STATUS_SUCCESS));
                $response->appendChild($this->_outputDom->createElementNS('uri:Move', 'DstMsgId', $newId));
            }
        }
        
        return $this->_outputDom;
    }
}
