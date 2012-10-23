<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync MeetingResponse command
 *
 * @package     Syncroton
 * @subpackage  Command
 */
class Syncroton_Command_MeetingResponse extends Syncroton_Command_Wbxml
{
    protected $_results = array();
    
    protected $_defaultNameSpace = 'uri:MeetingResponse';
    protected $_documentElement  = 'MeetingResponse';

    /**
     * parse MeetingResponse request
     */
    public function handle()
    {
        $dataController = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_CALENDAR, $this->_device, $this->_syncTimeStamp);
        
        $xml = simplexml_import_dom($this->_requestBody);

        foreach ($xml as $meetingResponse) {
            $request = new Syncroton_Model_MeetingResponse($meetingResponse);
            
            try {
                $calendarId = $dataController->setAttendeeStatus($request);
                
                $this->_results[] = array(
                    'calendarId' => $calendarId,
                    'request'    => $request,
                    'status'     => 1
                );
                
            } catch (Syncroton_Exception_Status_MeetingResponse $sesmr) {
                $this->_results[] = array(
                    'request' => $request,
                    'status'  => $sesmr->getCode()
                );
            }
        }

        if ($this->_logger instanceof Zend_Log)
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . " results: " . print_r($this->_results, true));
    }

    /**
     * generate MeetingResponse response
     */
    public function getResponse()
    {
        $this->_outputDom->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Search', 'uri:Search');
        
        $meetingResponse = $this->_outputDom->documentElement;

        foreach ($this->_results as $result) {
            $resultElement = $this->_outputDom->createElementNS('uri:MeetingResponse', 'Result');
            
            if (isset($result['request']->requestId)) {
                $resultElement->appendChild($this->_outputDom->createElementNS('uri:MeetingResponse', 'RequestId', $result['request']->requestId));
            } elseif (isset($result['request']->longId)) {
                $resultElement->appendChild($this->_outputDom->createElementNS('uri:Search', 'LongId', $result['request']->longId));
            }
            
            $resultElement->appendChild($this->_outputDom->createElementNS('uri:MeetingResponse', 'Status', $result['status']));
            
            if (isset($result['calendarId'])) {
                $resultElement->appendChild($this->_outputDom->createElementNS('uri:MeetingResponse', 'CalendarId', $result['calendarId']));
            }
            
            $meetingResponse->appendChild($resultElement);
        } 
        
        return $this->_outputDom;
    }
}
