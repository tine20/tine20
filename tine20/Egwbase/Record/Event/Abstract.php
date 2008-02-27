<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */


/**
 * class Tinebase_Record_Event_Abstract
 * 
 * Abstract event for record related events
 */
class Tinebase_Record_Event_Abstract extends Tinebase_Events_Abstract 
{
    /**
     * hold definition of observable
     *
     * @var Tinebase_Model_PersistentObserver
     */
	public $observable;

    
} // end of Tinebase_Record_Event_Abstract
?>