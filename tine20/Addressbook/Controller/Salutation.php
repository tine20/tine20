<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        add possiblity to change salutations (extend Tinebase_Controller_Record_Abstract)
 */

/**
 * contact controller for Addressbook
 *
 * @package     Addressbook
 * @subpackage  Controller
 */
class Addressbook_Controller_Salutation extends Tinebase_Controller_Abstract
{
    /**
     * the salutation backend
     *
     * @var Addressbook_Backend_Salutation
     */
    protected $_backend;
    
    /**
     * holds the instance of the singleton
     *
     * @var Addressbook_Controller_Salutation
     */
    private static $_instance = NULL;
        
    /**
     * the singleton pattern
     *
     * @return Addressbook_Controller_Salutation
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Addressbook_Controller_Salutation();
        }
        
        return self::$_instance;
    }
            
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SALUTATION);
        $this->_currentAccount = Tinebase_Core::getUser();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
        
    /**
     * get salutations
     *
     * @param string $_sort
     * @param string $_dir
     * @return Tinebase_Record_RecordSet of subtype Addressbook_Model_Salutation
     * 
     * @todo    use getAll from generic controller
     */
    public function getSalutations($_sort = 'id', $_dir = 'ASC')
    {
        $result = $this->_backend->getAll($_sort, $_dir);

        return $result;    
    }
    
    /**
     * get salutation by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Addressbook_Model_Salutation
     * 
     */
    public function getSalutation($_id)
    {
        $result = $this->_backend->get($_id);

        return $result;    
    }
}
