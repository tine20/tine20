<?php
/**
 * Tine 2.0
 * Leads Filter Class
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
class Crm_Model_LeadFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * if this is set, the filtergroup will be created using the configurationObject for this model
     *
     * @var string
     */
    protected $_configuredModel = Crm_Model_Lead::class;
}
