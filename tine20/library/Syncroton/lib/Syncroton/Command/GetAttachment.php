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
class Syncroton_Command_GetAttachment extends Syncroton_Command_Wbxml
{
    /**
     *
     * @var string
     */
    protected $_attachmentName;
    
    protected $_skipValidatePolicyKey = true;
    
    /**
     * process the XML file and add, change, delete or fetches data 
     *
     * @return resource
     */
    public function handle()
    {
        $this->_attachmentName = $this->_requestParameters['attachmentName'];
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
        
        if (PHP_SAPI !== 'cli') {
            // cache for 3600 seconds
            $maxAge = 3600;
            $now = new DateTime(null, new DateTimeZone('UTC'));
            header('Cache-Control: private, max-age=' . $maxAge);
            header("Expires: " . gmdate('D, d M Y H:i:s', $now->modify("+{$maxAge} sec")->getTimestamp()) . " GMT");
            
            // overwrite Pragma header from session
            header("Pragma: cache");
            
            #header('Content-Disposition: attachment; filename="' . $filename . '"');
            header("Content-Type: " . $attachment->contentType);
        }
        
        if (is_resource($attachment->data)) {
            fpassthru($attachment->data);
        } else {
            echo $attachment->data;
        }
    }
}
