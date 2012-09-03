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
 * class to handle ActiveSync SmartForward command
 *
 * @package     Syncroton
 * @subpackage  Command
 */
class Syncroton_Command_SmartForward extends Syncroton_Command_SendMail
{
    protected $_defaultNameSpace    = 'uri:ComposeMail';
    protected $_documentElement     = 'SmartForward';

    /**
     * this function generates the response for the client
     *
     * @return void
     */
    public function getResponse()
    {
        $dataController = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_EMAIL, $this->_device, $this->_syncTimeStamp);

        try {
            $dataController->forwardEmail($this->_source, $this->_mime, $this->_saveInSent, $this->_replaceMime);
        } catch (Syncroton_Exception_Status $ses) {
            if ($this->_logger instanceof Zend_Log)
                $this->_logger->warn(__METHOD__ . '::' . __LINE__ . " Sending email failed: " . $ses->getMessage());

            $response = new Syncroton_Model_SmartForward(array(
                'status' => $ses->getCode(),
            ));

            $response->appendXML($this->_outputDom->documentElement, $this->_device);

            return $this->_outputDom;
        }
    }
}
