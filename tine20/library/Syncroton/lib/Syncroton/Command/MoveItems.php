<?php

/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2012-2012 Kolab Systems AG (http://www.kolabsys.com)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @author      Aleksander Machniak <machniak@kolabsys.com>
 */

/**
 * class to handle ActiveSync MoveItems command
 *
 * @package     Syncroton
 * @subpackage  Command
 */
class Syncroton_Command_MoveItems extends Syncroton_Command_Wbxml
{
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
     */
    public function handle()
    {
        $xml = simplexml_import_dom($this->_requestBody);

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
                if ($move['srcFldId'] === $move['dstFldId']) {
                    throw new Syncroton_Exception_Status_MoveItems(Syncroton_Exception_Status_MoveItems::SAME_FOLDER);
                }

                try {
                    $sourceFolder = $this->_folderBackend->getFolder($this->_device, $move['srcFldId']);
                } catch (Syncroton_Exception_NotFound $e) {
                    throw new Syncroton_Exception_Status_MoveItems(Syncroton_Exception_Status_MoveItems::INVALID_SOURCE);
                }

                try {
                    $destinationFolder = $this->_folderBackend->getFolder($this->_device, $move['dstFldId']);
                } catch (Syncroton_Exception_NotFound $senf) {
                    throw new Syncroton_Exception_Status_MoveItems(Syncroton_Exception_Status_MoveItems::INVALID_DESTINATION);
                }

                $dataController = Syncroton_Data_Factory::factory($sourceFolder->class, $this->_device, $this->_syncTimeStamp);
                $newId          = $dataController->moveItem($move['srcFldId'], $move['srcMsgId'], $move['dstFldId']);

                $response->appendChild($this->_outputDom->createElementNS('uri:Move', 'Status', Syncroton_Command_MoveItems::STATUS_SUCCESS));
                $response->appendChild($this->_outputDom->createElementNS('uri:Move', 'DstMsgId', $newId));
            } catch (Syncroton_Exception_Status $e) {
                $response->appendChild($this->_outputDom->createElementNS('uri:Move', 'Status', $e->getCode()));
            } catch (Exception $e) {
                $response->appendChild($this->_outputDom->createElementNS('uri:Move', 'Status', Syncroton_Exception_Status::SERVER_ERROR));
            }
        }

        return $this->_outputDom;
    }
}
