<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * event fired before a new container is created
 *
 * @package     Tinebase
 * @subpackage  Container
 */
class Tinebase_Event_Container_BeforeCreate extends Tinebase_Event_Abstract
{
    /**
     * @var String
     */
    public $accountId;
    
    /**
     * @var Tinebase_Model_Container
     */
    public $container;
    
    /**
     * @var Tinebase_Model_Grants
     */
    public $grants;
}
