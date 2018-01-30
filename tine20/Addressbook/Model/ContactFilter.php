<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Addressbook_Model_ContactFilter
 * 
 * @package     Addressbook
 * @subpackage  Filter
 * 
 * @todo add bday filter
 */
class Addressbook_Model_ContactFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * if this is set, the filtergroup will be created using the configurationObject for this model
     *
     * @var string
     */
    protected $_configuredModel = Addressbook_Model_Contact::class;
}
