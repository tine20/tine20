<?php
/**
 * Tine 2.0
 * 
 * @package     MailFiler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = MailFiler_Model_DownloadLink::class;
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'             => array('filter' => 'Tinebase_Model_Filter_Id'),
        'node_id'        => array('filter' => 'Tinebase_Model_Filter_Id'),
    );
}
