<?php
/**
 * Tine 2.0
 * 
 * @package     MailFiler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * DownloadLink filter Class
 * @package     MailFiler
 */
class MailFiler_Model_DownloadLinkFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'MailFiler';
    
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'MailFiler_Model_DownloadLinkFilter';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'MailFiler_Model_DownloadLink';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'             => array('filter' => 'Tinebase_Model_Filter_Id'),
        'node_id'        => array('filter' => 'Tinebase_Model_Filter_Id'),
    );
}
