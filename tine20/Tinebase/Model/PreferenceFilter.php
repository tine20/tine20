<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        add acl filter (only admins (?) can see other users prefs and set defaults/forced prefs)
 * @todo        add facade for user/group/anyone filter -> PreferenceAccountFilter
 */

/**
 *  preference filter filter class
 * 
 * @package     Tinebase
 * @subpackage  Filter 
 */
class Tinebase_Model_PreferenceFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';

    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Tinebase_Model_PreferenceFilter';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('name', 'value'))),
        'application_id' => array('filter' => 'Tinebase_Model_Filter_Id'),
        'account'        => array('filter' => 'Tinebase_Model_PreferenceAccountFilter'),
        'account_id'     => array('filter' => 'Tinebase_Model_Filter_Int'),
        'account_type'   => array('filter' => 'Tinebase_Model_Filter_Text'),
        'name'           => array('filter' => 'Tinebase_Model_Filter_Text'),
        'type'           => array('filter' => 'Tinebase_Model_Filter_Text'),
    );    
}
