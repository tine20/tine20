<?php
/**
 * Tine 2.0
 *
 * @package     Egwbase
 * @subpackage  Events
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

interface Egwbase_Events_Interface
{
    public function handleEvents(Egwbase_Events_Abstract $_eventObject);
}