<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Role Filter Class
 * @package     Tinebase
 * @subpackage  Acl
 *
 */
class Tinebase_Model_RoleMemberFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';

    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Tinebase_Model_RoleMember';

    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'                    => array('filter' => 'Tinebase_Model_Filter_Int'),
        'role_id'               => array('filter' => 'Tinebase_Model_Filter_Int'),
        'account_type'          => array('filter' => 'Tinebase_Model_Filter_Int'),
        'account_id'            => array('filter' => 'Tinebase_Model_Filter_Int'),
    );
}
