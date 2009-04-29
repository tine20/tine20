<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        set timestamp field (add default to model?)
 */

/**
 * sql backend class for Felamimail folders
 *
 * @package     Felamimail
 */
class Felamimail_Backend_Folder extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'felamimail_folder';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Felamimail_Model_Folder';

    /**
     * get saved folder record by backend and globalname
     *
     * @param string $_backendId
     * @param string $_globalName
     * @return Felamimail_Model_Folder
     */
    public function getByBackendAndGlobalName($_backendId, $_globalName)
    {
        $filter = new Felamimail_Model_FolderFilter(array(
            array('field' => 'backend_id', 'operator' => 'equals', 'value' => $_backendId),
            array('field' => 'globalname', 'operator' => 'equals', 'value' => $_globalName),
        ));
        
        $folders = $this->search($filter);
        
        if (count($folders) > 0) {
            $result = $folders->getFirstRecord();
        } else {
            throw new Tinebase_Exception_NotFound("Folder $_globalName not found.");
        }
        
        return $result;
    }
}
