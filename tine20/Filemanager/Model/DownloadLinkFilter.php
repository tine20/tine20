<?php
/**
 * Tine 2.0
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * DownloadLink filter Class
 * @package     Filemanager
 */
class Filemanager_Model_DownloadLinkFilter extends Tinebase_Model_Filter_FilterGroup 
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Filemanager';
    
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Filemanager_Model_DownloadLinkFilter';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Filemanager_Model_DownloadLink';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'             => array('filter' => 'Tinebase_Model_Filter_Id'),
        'node_id'        => array('filter' => 'Tinebase_Model_Filter_Id'),
    );
}
