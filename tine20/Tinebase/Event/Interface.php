<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * interface for all classes which can handle events
 *
 * @package     Tinebase
 * @subpackage  Event
 */
interface Tinebase_Event_Interface
{
    /**
     * this functions handles the events
     *
     * @param Tinebase_Event_Abstract $_eventObject the eventobject
     */
    public function handleEvent(Tinebase_Event_Abstract $_eventObject);
    
    /**
     * suspend processing of event
     */
    public function suspendEvents();

    /**
     * resume processing of events
     */
    public function resumeEvents();
}
