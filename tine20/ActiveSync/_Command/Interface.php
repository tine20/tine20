<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 */
 
interface ActiveSync_Command_Interface 
{
    /**
     * the constructor
     *
     * @param  mixed                    $_requestBody
     */
    public function __construct($_requestBody, ActiveSync_Model_Device $_device = null, $_policyKey = null);
    
    /**
     * process the XML file and add, change, delete or fetches data 
     */
    public function handle();

    public function getResponse();
}
