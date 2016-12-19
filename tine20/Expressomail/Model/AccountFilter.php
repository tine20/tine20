<?php
/**
 * Tine 2.0
 * 
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Account filter Class
 * @package     Expressomail
 */
class Expressomail_Model_AccountFilter extends Tinebase_Model_Filter_FilterGroup 
{
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Expressomail_Model_AccountFilter';
    
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Expressomail';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Expressomail_Model_Account';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'            => array('filter' => 'Tinebase_Model_Filter_Id'),
        'user_id'       => array('filter' => 'Tinebase_Model_Filter_Id'),
        'name'          => array('filter' => 'Tinebase_Model_Filter_Text'),
    );
}
