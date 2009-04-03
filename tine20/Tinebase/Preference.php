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
 * @todo        add preference model
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
     */
    private function __construct()
    {
        parent::__construct();
    }
}
