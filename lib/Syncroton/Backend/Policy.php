<?php
/**
 * Syncroton
 *
 * @package     Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Sync command
 *
 * @package     Backend
 */
 
class Syncroton_Backend_Policy extends Syncroton_Backend_ABackend #implements Syncroton_Backend_IDevice
{
    protected $_tableName = 'policy';
    
    protected $_modelClassName = 'Syncroton_Model_Policy';
    
    protected $_modelInterfaceName = 'Syncroton_Model_IPolicy';
}
