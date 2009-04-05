<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Preference.php 7161 2009-03-04 14:27:07Z p.schuele@metaways.de $
 * 
 * @todo        finish implementation
 */


/**
 * backend for persistent filters
 *
 * @package     Timetracker
 * @subpackage  Backend
 */
class Tinebase_Preference extends Tinebase_Backend_Sql_Abstract
{
    /**
     * timezone pref const
     *
     */
    const TIMEZONE = 'timezone';

    /**
     * application
     *
     * @var string
     */
    protected $_application = 'Tinebase';    
    
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'preferences';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_Preference';
    
    /*************** singleton ****************/
    
    /**
     * don't clone. Use the singleton.
     */
    private function __clone()
    {
        
    }

    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Preference
     */
    private static $_instance = NULL;
        
    /**
     * the singleton pattern
     *
     * @return Tinebase_Preference
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Preference;
        }
        
        return self::$_instance;
    }

    /**
     * the private constructor
     *
     * @todo find a way to make constructor private even if parent is public (?)
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**************** public funcs **************/
    
    /**
     * get value of preference
     *
     * @param string $_preference
     * @return string
     */
    public function getValue($_preference) {
        return $this->getValueForUser($_preference, Tinebase_Core::getUser()->getId());
    }
    
    /**
     * get value of preference for a user
     *
     * @param string $_preference
     * @param integer $_userId
     * @return string
     * 
     * @todo finish implementation
     */
    public function getValueForUser($_preference, $_userId) {
        
    }
}
