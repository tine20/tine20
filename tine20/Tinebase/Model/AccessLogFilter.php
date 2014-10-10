<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 *  access log filter class
 * 
 * @package     Tinebase
 * @subpackage  Filter 
 */
class Tinebase_Model_AccessLogFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Tinebase_Model_AccessLog';
    
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Tinebase_Model_AccessLogFilter';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'         => array('filter' => 'Tinebase_Model_Filter_Query',        'options' => array('fields' => array('login_name', 'ip', 'clienttype'))),
        'sessionid'     => array('filter' => 'Tinebase_Model_Filter_Text'),
        'ip'            => array('filter' => 'Tinebase_Model_Filter_Text'),
        'clienttype'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'li'            => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'lo'            => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'account_id'    => array('filter' => 'Tinebase_Model_Filter_User'),
        'result'        => array('filter' => 'Tinebase_Model_Filter_Int'),
        'user_agent'    => array('filter' => 'Tinebase_Model_Filter_Text')
    );
}
