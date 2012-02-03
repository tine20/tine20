<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 */
 
interface Syncope_Command_Interface 
{
    /**
     * the constructor
     *
     * @param  mixed                    $_requestBody
     */
    public function __construct($_requestBody, Syncope_Model_IDevice $_device, $_policyKey);
    
    /**
     * process the XML file and add, change, delete or fetches data 
     */
    public function handle();

    public function getResponse();
}
