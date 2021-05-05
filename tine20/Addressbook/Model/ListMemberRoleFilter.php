<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Addressbook_Model_ListMemberRoleFilter
 *
 * @package     Addressbook
 * @subpackage  Filter
 */
class Addressbook_Model_ListMemberRoleFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Addressbook';

    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = Addressbook_Model_ListMemberRole::class;

    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'                => array('filter' => 'Tinebase_Model_Filter_Id'),
        'contact_id'        => [
            'filter'            => Tinebase_Model_Filter_ForeignId::class,
            'options'           => [
                'controller'        => Addressbook_Controller_Contact::class,
                'filtergroup'       => Addressbook_Model_ContactFilter::class
            ]
        ],
        'list_id'           => array('filter' => Tinebase_Model_Filter_ForeignId::class,
            'options'           => [
                'controller'        => Addressbook_Controller_List::class,
                'filtergroup'       => Addressbook_Model_ListFilter::class
            ]),
        'list_role_id'      => array('filter' => Tinebase_Model_Filter_ForeignId::class,
            'options'           => [
                'controller'        => Addressbook_Controller_ListRole::class,
                'filtergroup'       => Addressbook_Model_ListRoleFilter::class
            ]),
    );
}
