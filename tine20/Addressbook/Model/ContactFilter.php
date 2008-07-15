<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        generalise that?
 */

/**
 * Addressbook Filter Class
 * @package Addressbook
 */
class Addressbook_Model_ContactFilter extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Addressbook';
    
    protected $_validators = array(
        'id'                   => array('allowEmpty' => true,  'Int'   ),
        
        'containerType'        => array('allowEmpty' => true           ),
        'owner'                => array('allowEmpty' => true           ),
        'container'            => array('allowEmpty' => true           ),

        'query'                => array('allowEmpty' => true           ),
        'tag'                  => array('allowEmpty' => true           ),
        
    );
    
    /**
     * gets record related properties
     * 
     * @param string name of property
     * @return mixed value of property
     */
    public function __get($_name)
    {
        switch ($_name) {
            case 'container':
                $this->_resolveContainer();
                break;
            default:
        }
        return parent::__get($_name);
    }
    
    /**
     * Resolves containers from selected nodes
     * 
     * @throws Exception
     * @return void
     */
    protected function _resolveContainer()
    {
        if (isset($this->_properties['container']) && is_array($this->_properties['container'])) {
            return;
        }
        if (!$this->containerType) {
            throw new Exception('You need to set a containerType.');
        }
        if ($this->containerType == 'Personal' && !$this->owner) {
            throw new Exception('You need to set an owner when containerType is "Personal".');
        }
        
        $cc = Tinebase_Container::getInstance();
        switch($this->containerType) {
            case 'all':
                $containers = $cc->getContainerByACL(Zend_Registry::get('currentAccount'), $this->_application, Tinebase_Container::GRANT_READ);
                break;
            case 'personal':
                $containers = Zend_Registry::get('currentAccount')->getPersonalContainer($this->_application, $this->owner, Tinebase_Container::GRANT_READ);
                break;
            case 'shared':
                $containers = Zend_Registry::get('currentAccount')->getSharedContainer($this->_application, Tinebase_Container::GRANT_READ);
                break;
            case 'otherUsers':
                $containers = Zend_Registry::get('currentAccount')->getOtherUsersContainer($this->_application, Tinebase_Container::GRANT_READ);
                break;
            case 'singleContainer':
                $this->_properties['container'] = array($this->_properties['container']);
                return;
            default:
                throw new Exception('containerType not supported.');
        }
        $container = array();
        foreach ($containers as $singleContainer) {
            $container[] = $singleContainer->getId();
        }
        
        $this->_properties['container'] = $container;
    }    
}
