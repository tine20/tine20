<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * event class for deleted resources
 *
 * @package     Calendar
 */
class Calendar_Event_DeleteResource extends Tinebase_Event_Abstract
{
    /**
     * the resource to be deleted
     *
     * @var Calendar_Model_Resource
     */
    public $resource;
}
