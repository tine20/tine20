<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * controller abstract for applications with event handling
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
abstract class Tinebase_Controller_Event extends Tinebase_Controller_Abstract implements Tinebase_Event_Interface
{
    /**
     * disable events on demand
     * 
     * @var mixed   false => no events filtered, true => all events filtered, array => disable only specific events
     */
    protected $_disabledEvents = false;
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Event_Interface::handleEvent()
     */
    public function handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        if ($this->_disabledEvents === true) {
            // nothing todo
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                ' events are disabled. do nothing'
            );
            return;
        }
        
        $this->_handleEvent($_eventObject);
    }
    
    /**
     * implement logic for each controller in this function
     * 
     * @param Tinebase_Event_Abstract $_eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        // do nothing
        //if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' (' . __LINE__ . ') handle event of type ' . get_class($_eventObject));
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Event_Interface::suspendEvents()
     */
    public function suspendEvents()
    {
        $this->_disabledEvents = true;
    }

    /**
     * (non-PHPdoc)
     * @see Tinebase_Event_Interface::resumeEvents()
     */
    public function resumeEvents()
    {
        $this->_disabledEvents = false;
    }
}
