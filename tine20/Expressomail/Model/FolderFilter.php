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
 * Folder filter Class
 * @package     Expressomail
 */
class Expressomail_Model_FolderFilter extends Tinebase_Model_Filter_FilterGroup 
{
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Expressomail_Model_FolderFilter';
    
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Expressomail';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Expressomail_Model_Folder';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'            => array('filter' => 'Tinebase_Model_Filter_Id'),
        'globalname'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'localname'     => array('filter' => 'Tinebase_Model_Filter_Text'),
        'parent'        => array('filter' => 'Tinebase_Model_Filter_Text'),
        'account_id'    => array('filter' => 'Tinebase_Model_Filter_Text'),
    );
}
