<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */


/**
 * class Tinebase_Record_Event_Abstract
 * 
 * Abstract event for record related events
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Record_Event_Abstract extends Tinebase_Event_Abstract 
{
    /**
     * hold definition of observable
     *
     * @var Tinebase_Model_PersistentObserver
     */
    public $observable;

    
}
