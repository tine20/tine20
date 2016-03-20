<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Fernando Alberto Reuter Wendt <fernando-alberto.wendt@serpro.gov.br>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
                Copyright (c) 2015 SERPRO GmbH (https://www.serpro.gov.br)
 *
 */

/**
 * sql backend class for Expressomail Scheduler
 *
 * @package     Expressomail
 */
class Expressomail_Backend_Scheduler extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'expressomail_backup_scheduler';

    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Expressomail_Model_Scheduler';

    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;

   /**
    * default column(s) for count
    *
    * @var string
    */
    protected $_defaultCountCol = 'account_id';

    /**
     * foreign tables (key => tablename)
     *
     * @var array
     */
    protected $_foreignTables = array(
        'contact_id_contacts'    => array(
            'table'         => 'accounts',
            'field'         => 'account_id',
            'joinOn'        => 'id',
            'singleValue'   => TRUE,
            'preserve'      => TRUE,
        ),
    );

    /**
     * (non-PHPdoc)
     * @see Tinebase_Backend_Sql_Abstract::__construct()
     */
    public function __construct($_dbAdapter = NULL, $_options = array())
    {
        parent::__construct($_dbAdapter, $_options);
    }

    /**
     * Creates new entry
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_Database
     * @throws  Tinebase_Exception
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        try{
            $newEntry = parent::create($_record);
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 'Successfully added new schedule task on database system.');
            return $newEntry;
        } catch (Tinebase_Exception_Backend_Database $ex){
            Tinebase_Core::getLogger()->error(__METHOD__ . '::' . __LINE__ . 'Database insertion fails at backend unit: ' . print_r($ex, true));
            return $ex->getMessage();
        }
    }

    /**
     * Find entry
     *
     * @param   array $fetchArray
     * @return  integer
     * @throws  Tinebase_Exception_Database
     */
    public function findOne($fetchArray)
    {
        try{
            $select = "SELECT status FROM " . SQL_TABLE_PREFIX . $this->_tableName 
                    . " WHERE "
                    . "account_id='" . $fetchArray[0] . "' AND "
                    . "folder='" . $fetchArray[1] . "' AND "
                    . "status='" . $fetchArray[2] . "' AND "
                    . "priority=" . $fetchArray[3] . " AND "
                    . "is_deleted=0;";
            $stmt = $this->_db->fetchRow($select);
            return ($stmt ? count($stmt) : 0);
        } catch (Tinebase_Exception_Backend_Database $edb){
            Tinebase_Core::getLogger()->error(__METHOD__ . '::' . __LINE__ . 'Database insertion fails at backend unit: ' . print_r($edb, true));
            return $edb->getMessage();
        }
    }
}