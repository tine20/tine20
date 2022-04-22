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
class Tinebase_Model_CustomField_GrantFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';

    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'customfield_id'               => array('filter' => 'Tinebase_Model_Filter_Int'),
    );
}
