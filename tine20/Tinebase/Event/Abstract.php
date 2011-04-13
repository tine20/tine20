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
 * base class for all events
 *
 * @package     Tinebase
 * @subpackage  Event
 */
abstract class Tinebase_Event_Abstract
{
    /**
     * @var string
     */
    protected $_id;
    
    public function __construct(array $_values = array())
    {
        $this->_id = Tinebase_Record_Abstract::generateUID();
        
        foreach($_values as $key => $value) {
            $this->$key = $value;
        }
    }
    
    /**
     * get id of event
     * 
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }
}
