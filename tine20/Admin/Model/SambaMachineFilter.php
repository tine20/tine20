<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Samba
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Admin Samba Machine Filter
 *
 * @package     Admin
 * @subpackage  Samba
 */
class Admin_Model_SambaMachineFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_applicationName = 'Admin';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Admin_Model_SambaMachine';
    
    protected $_filterModel = array(
        'query'                => array(
            'filter' => 'Tinebase_Model_Filter_Query', 
            'options' => array(
                'fields' => array('accountLoginName')
            )
        ),
    );
}
