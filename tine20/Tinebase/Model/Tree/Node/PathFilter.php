<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Tinebase_Model_Tree_Node_PathFilter
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 */
class Tinebase_Model_Tree_Node_PathFilter extends Tinebase_Model_Filter_Text 
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',       
    );
    
    /**
     * the parsed path record
     * 
     * @var Tinebase_Model_Tree_Node_Path
     */
    protected $_path = NULL;
    
    /**
     * @var array one of these grants must be met
     */
    protected $_requiredGrants = array(
        Tinebase_Model_Grants::GRANT_READ
    );
    
    /**
     * set options 
     *
     * @param  array $_options
     * @throws Tinebase_Exception_Record_NotDefined
     */
    protected function _setOptions(array $_options)
    {
        $_options['ignoreAcl'] = isset($_options['ignoreAcl']) ? $_options['ignoreAcl'] : false;
        
        $this->_options = $_options;
    }
    
    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        $this->_parsePath();
        
        $this->_addParentIdFilter($_select, $_backend);
        
        if (! $this->_path->container) {
            $this->_addContainerTypeFilter($_select, $_backend);
        }
    }
    
    /**
     * parse given path (filter value): check validity, set container type, do replacements
     */
    protected function _parsePath()
    {
        $this->_path = ($this->_value instanceof Tinebase_Model_Tree_Node_Path) ? $this->_value : new Tinebase_Model_Tree_Node_Path(array(
            'flatpath'  => $this->_value
        ));
        
        if (! $this->_options['ignoreAcl'] && ! Tinebase_Core::getUser()->hasRight($this->_path->application->name, Tinebase_Acl_Rights_Abstract::RUN)) {
            throw new Tinebase_Exception_AccessDenied('You don\'t have the right to run this application');
        }
    }

    /**
     * adds parent id filter sql
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     */
    protected function _addParentIdFilter($_select, $_backend)
    {
        $node = Tinebase_FileSystem::getInstance()->stat($this->_path->statpath);

        $parentIdFilter = new Tinebase_Model_Filter_Text('parent_id', 'equals', $node->getId());
        $parentIdFilter->appendFilterSql($_select, $_backend);
    }

    /**
     * adds container type filter sql
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     */
    protected function _addContainerTypeFilter($_select, $_backend)
    {
        $currentAccount = Tinebase_Core::getUser();
        $appName        = $this->_path->application->name;
        $ignoreAcl      = $this->_options['ignoreAcl'];
        
        switch ($this->_path->containerType) {
            case Tinebase_Model_Container::TYPE_PERSONAL:
                $names = Tinebase_Container::getInstance()->getPersonalContainer($currentAccount, $appName,
                    $currentAccount, $this->_requiredGrants, $ignoreAcl)->getArrayOfIds();
                break;
            case Tinebase_Model_Container::TYPE_SHARED:
                $names = Tinebase_Container::getInstance()->getSharedContainer($currentAccount, $appName,
                    $this->_requiredGrants, $ignoreAcl)->getArrayOfIds();
                break;
            case Tinebase_Model_Container::TYPE_OTHERUSERS:
                if ($this->_path->containerOwner) {
                    $owner = Tinebase_User::getInstance()->getFullUserByLoginName($this->_path->containerOwner);
                    $names = Tinebase_Container::getInstance()->getPersonalContainer($currentAccount, $appName,
                        $owner, $this->_requiredGrants, $ignoreAcl)->getArrayOfIds();
                } else {
                    $users = Tinebase_Container::getInstance()->getOtherUsers($currentAccount, $appName, $this->_requiredGrants);
                    $names = array();
                    // @todo use getMultiple for full users
                    foreach ($users as $user) {
                        $names[] = Tinebase_User::getInstance()->getFullUserById($user)->accountLoginName;
                    }
                }
                break;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Filter names: ' . print_r($names, TRUE));
        
        $nameFilter = new Tinebase_Model_Filter_Text('name', 'in', $names);
        $nameFilter->appendFilterSql($_select, $_backend);
    }
}
