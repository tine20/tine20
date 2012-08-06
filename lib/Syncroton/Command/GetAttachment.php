<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync GetAttachment command
 *
 * @package     Syncroton
 * @subpackage  Command
 */
 
class Syncroton_Command_GetAttachment
{
    /**
     * informations about the currently device
     *
     * @var Syncroton_Model_Device
     */
    protected $_device;
    
    /**
     * timestamp to use for all sync requests
     *
     * @var DateTime
     */
    protected $_syncTimeStamp;
    
    /**
     *
     * @var string
     */
    protected $_attachmentName;
    
    /**
     * the constructor
     *
     * @param  mixed                   $requestBody
     * @param  Syncroton_Model_Device  $device
     * @param  string                  $policyKey
     */
    public function __construct($requestBody, Syncroton_Model_IDevice $device, $policyKey)
    {
        $this->_policyKey     = $policyKey;
        $this->_device        = $device;
        $this->_syncTimeStamp = new DateTime(null, new DateTimeZone('UTC'));
    }
    
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @return resource
     */
    public function handle()
    {
        $this->_attachmentName = $_GET['AttachmentName'];
    }
    
    /**
     * this function generates the response for the client
     * 
     * @return void
     */
    public function getResponse()
    {
        $dataController = Syncroton_Data_Factory::factory(Syncroton_Data_Factory::CLASS_EMAIL, $this->_device, $this->_syncTimeStamp);
        
        $attachment = $dataController->getFileReference($this->_attachmentName);
        
        // cache for 3600 seconds
        $maxAge = 3600;
        $now = new DateTime(null, new DateTimeZone('UTC'));
        header('Cache-Control: private, max-age=' . $maxAge);
        header("Expires: " . gmdate('D, d M Y H:i:s', $now->modify("+{$maxAge} sec")->getTimestamp()) . " GMT");
        
        // overwrite Pragma header from session
        header("Pragma: cache");
        
        #header('Content-Disposition: attachment; filename="' . $filename . '"');
        header("Content-Type: " . $attachment->ContentType);
        
        if (is_resource($attachment->Data)) {
            fpassthru($attachment->Data);
        } else {
            echo $attachment->Data;
        }
    }
}
