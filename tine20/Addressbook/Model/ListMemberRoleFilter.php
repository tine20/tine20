<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Addressbook_Model_ListMemberRoleFilter';

    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Addressbook';

    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Addressbook_Model_ListMemberRole';

    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'                => array('filter' => 'Tinebase_Model_Filter_Id'),
        'contact_id'        => array('filter' => 'Tinebase_Model_Filter_Id'),
        'list_id'           => array('filter' => 'Tinebase_Model_Filter_Id'),
        'list_role_id'      => array('filter' => 'Tinebase_Model_Filter_Id'),
    );
}