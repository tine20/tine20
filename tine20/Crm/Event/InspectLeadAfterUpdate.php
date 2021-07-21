<?php
/**
 * Tine 2.0
 * event class for Lead inspection after update
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
class Crm_Event_InspectLeadAfterUpdate extends Tinebase_Event_Observer_Abstract
{
    /**
     * the Lead to inspect
     *
     * @var Crm_Model_Lead
     */
    public $observable;
}
