<?php
/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:FolderFilter.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 *
 */

/**
 * Folder filter Class
 * @package     Felamimail
 */
class Felamimail_Model_FolderFilter extends Tinebase_Model_Filter_FilterGroup 
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'            => array('filter' => 'Tinebase_Model_Filter_Id'),
        'globalname'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'parent'        => array('filter' => 'Tinebase_Model_Filter_Text'),
        'backend_id'    => array('filter' => 'Tinebase_Model_Filter_Text'),
    );
}
