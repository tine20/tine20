<?php
/**
 * Tine 2.0
 * 
 * @package     Egwbase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */


/**
 * class Egwbase_Record_Event_Abstract
 * 
 * Abstract event for record related events
 */
class Egwbase_Record_Event_Abstract extends Egwbase_Events_Abstract 
{
    /**
     * hold definition of observable
     *
     * @var Egwbase_Model_PersistentObserver
     */
	public $observable;

    
} // end of Egwbase_Record_Event_Abstract
?>