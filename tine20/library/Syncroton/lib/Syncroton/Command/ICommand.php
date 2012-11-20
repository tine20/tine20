<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * interface for all Syncroton command classes
 *
 * @package     Syncroton
 * @subpackage  Command
 */
interface Syncroton_Command_ICommand 
{
    /**
     * constructor of this class
     * 
     * @param resource                 $_requestBody
     * @param Syncroton_Model_IDevice  $_device
     * @param string                   $_policyKey
     */
    public function __construct($_requestBody, Syncroton_Model_IDevice $_device, $_policyKey);
    
    /**
     * process the incoming data 
     */
    public function handle();

    /**
     * create the response
     */
    public function getResponse();
    
    /**
     * return headers of command
     * 
     * @return array list of headers
     */
    public function getHeaders();
}
