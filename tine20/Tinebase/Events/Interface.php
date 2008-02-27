<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Events
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * interface for all classes which can handle events
 *
 * @package     Tinebase
 * @subpackage  Events
 */
interface Tinebase_Events_Interface
{
    /**
     * this functions handles the events
     *
     * @param Tinebase_Events_Abstract $_eventObject the eventobject
     */
    public function handleEvents(Tinebase_Events_Abstract $_eventObject);
}