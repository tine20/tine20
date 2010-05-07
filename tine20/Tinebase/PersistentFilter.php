<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  PersistentFilter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */


/**
 * persistent filter controller
 * 
 * @todo rework account_id to container_id to let Persistent_Filters be organised
 *       in standard contaienr / grants way. This depends on container class to cope
 *       with multiple models per app which is not yet implementet (2010-05-05)
 * @todo rethink is_default. defaults should be handeld by preferences. Unfortunally
 *       preferences can't cope yet with admin/nonadmin prefs. So the admin could 
 *       define/force a faforite the user has no rights for ;-( (2010-05-05)
 *      
 */
class Tinebase_PersistentFilter extends Tinebase_Controller_Record_Abstract
{
    /**
     * application name
     *
     * @var string
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * check for container ACLs?
     *
     * @var boolean
     */
    protected $_doContainerACLChecks = FALSE;

    /**
     * do right checks - can be enabled/disabled by _setRightChecks
     * 
     * @var boolean
     */
    protected $_doRightChecks = FALSE;
    
    /**
     * delete or just set is_delete=1 if record is going to be deleted
     *
     * @var boolean
     */
    protected $_purgeRecords = FALSE;
    
    /**
     * ommit mod log for this records
     * 
     * @var boolean
     */
    protected $_ommitModLog = TRUE;
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_PersistentFilter';
    
    /**
     * @var Tinebase_PersistentFilter
     */
    private static $_instance = NULL;
    
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_backend         = new Tinebase_PersistentFilter_Backend_Sql();
        $this->_currentAccount  = Tinebase_Core::getUser();
    }

    /**
     * don't clone. Use the singleton.
     */
    private function __clone() 
    {
        
    }
    
    /**
     * singleton
     *
     * @return Tinebase_PersistentFilter
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_PersistentFilter();
        }
        return self::$_instance;
    }
    
    /**
     * returns persistent filter identified by id
     * 
     * @param  string $_id
     * @return Tinebase_Model_Filter_FilterGroup
     */
    public static function getFilterById($_id)
    {
        $persistentFilter = self::getInstance()->get($_id);
        
        return $persistentFilter->filters;
    }
    
    /**
     * helper fn for prefereces
     * 
     * @param  string $_appName
     * @param  string $_accountId
     * @param  string $_returnDefaultId only return id of default identified by given name
     * @return array|string filterId => translated name
     */
    public static function getPreferenceValues($_appName, $_accountId = NULL, $_returnDefaultId = NULL)
    {
        $i18n = Tinebase_Translation::getTranslation($_appName);
        $pfilters = self::getInstance()->search(new Tinebase_Model_PersistentFilterFilter(array(
            array('field' => 'application_id', 'operator' => 'equals', 'value' => Tinebase_Application::getInstance()->getApplicationByName($_appName)->getId()),
            array('field' => 'account_id',     'operator' => 'equals', 'value'  => $_accountId ? $_accountId : Tinebase_Core::getUser()->getId()),
        )));
        
        if (! $_returnDefaultId) {
            $result = array();
            foreach ($pfilters as $pfilter) {
                $result[] = array($pfilter->getId(), $i18n->translate($pfilter->name));
            }
            return $result;
        } else {
            $filter = $pfilters->filter('name', $_returnDefaultId)->getFirstRecord();
            return $filter ? $filter->getId() : NULL;
        }
    }
    
    /**
     * inspect creation of one record
     * 
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectCreate(Tinebase_Record_Interface $_record)
    {
        $this->_sanitizeAccountId($_record);
    }
    
    /**
     * inspect update of one record
     * 
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectUpdate($_record, $_oldRecord)
    {
        $this->_sanitizeAccountId($_record);
    }
    
    /**
     * set account_id to currentAccount if user has no MANAGE_SHARED_FAVORITES right
     * 
     * @param  Tinebase_Record_Interface $_record
     * @return void
     */
    protected function _sanitizeAccountId($_record)
    {
        if (! Tinebase_Core::getUser()->hasRight($_record->application_id, Tinebase_Acl_Rights::MANAGE_SHARED_FAVORITES)) {
            $_record->account_id = $this->_currentAccount->getId();
        }
    }
}
