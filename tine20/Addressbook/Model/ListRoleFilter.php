<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Addressbook_Model_ListRoleFilter
 * 
 * @package     Addressbook
 * @subpackage  Filter
 */
class Addressbook_Model_ListRoleFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * if this is set, the filtergroup will be created using the configurationObject for this model
     *
     * @var string
     */
    protected $_configuredModel = Addressbook_Model_ListRole::class;
}
