<?php
/**
 * egroupware 2.0
 * 
 * @package     Tasks
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: $
 *
 */

/**
 * Migration from  egw 1.4 table layout to tasks 2.0 table layout
 * 
 * @package Tasks
 * @subpackage Setup
 * @todo maybe we should create a mapping table infolog_id -> taks_id to be able
 * to megrate cut-off fields later?
 * cut-off:
 *   -info_type         ?
 *   -info_planned-time pm interface -> later
 *   -info_used_time    pm interface -> later
 *   -info_confirm      ?
 *   -info_link_id      obsolete?
 *   -pl_id             ?
 *   -info_price        pm interface -> later
 *   -info_custom_from  ?
 *   -custom fields      defer for later
 * transform:
 *   -contact is info_from, if info_addr is not an email.
 *   -duration is info_enddate - info_startdate
 */
class Tasks_Setup_MigrateFromEgw14 
{

    /**
     * Mapping Tasks Model / Egw 1.4 Infolog Table
     *
     * @var array
     */
    protected static $_mapping = array(
        // egw record fields
        'container'            => '',
        'created_by'           => 'info_owner',
        'creation_time'        => NULL,
        'last_modified_by'     => 'info_modifier',
        'last_modified_time'   => 'info_datemodified',
        'is_deleted'           => '',
        'deleted_time'         => NULL,
        'deleted_by'           => '',
        // task only fields
        'identifier'           => 'info_id',
        'percent'              => 'info_percent',
        'completed'            => 'info_datecompleted',
        // ical common fields
        'class'                => 'info_access',
        'description'          => 'info_des',
        'geo'                  => '',
        'location'             => 'info_location',
        'organizer'            => 'info_responsible',
        'priority'             => 'info_priority',
        'status'               => 'info_status',
        'summaray'             => 'info_subject',
        'url'                  => '',
        // ical common fields with multiple appearance
        'attach'                => '',
        'attendee'              => '',
        'categories'            => 'info_cat',
        'comment'               => '',
        'contact'               => 'info_from',
        'related'               => 'info_id_parent',
        'resources'             => '',
        'rstatus'               => '',
        // scheduleable interface fields
        'dtstart'               => 'info_startdate',
        'duration'              => '',
        'recurid'               => '',
        // scheduleable interface fields with multiple appearance
        'exdate'                => '',
        'exrule'                => '',
        'rdate'                 => '',
        'rrule'                 => '',
    );
    
    /**
     * Migrates Infologs from egw 1.4 to Tasks.
     * 
     * @return void
     */
    public static function MigrateInfolog2Tasks()
    {
        $tasksBackend = Tasks_Backend_Factory::factory(Tasks_Backend_Factory::SQL);

        $db = Zend_Registry::get('dbAdapter');
        $stmt = $db->query($db->select()
            ->from('egw_infolog')
        );
        
        while ($infolog = $stmt->fetchObject()) {
            $Task = self::infolog2Task($infolog);
            $Task->container = self::getOwnersContainer($infolog->info_owner);
            $tasksBackend->createTask($Task);
        }
    }
    
    /**
     * Converts info_id to an uid
     *
     * @param int $_id
     * @return string uid
     */
    protected static function id2uid($_id)
    {
        return $_id;
    }
    
    /**
     * Returns Container we can migrate tasks to
     * 
     * @todo Make shure we use same containers in calendar and tasks!
     * ??? what about loose coupling in this case???
     * 
     * @param int $_owner
     * @return int Egwbase_Container::container_id
     */
    protected static function getOwnersContainer($_owner)
    {
        static $containers = array();
        
        if (!isset($containers[$_owner])) {
            $containers[$_owner] = Egwbase_Container::getInstance()->addContainer(
                'Tasks',
                'My Tasks (from egw 1.4 migration)',
                Egwbase_Container::TYPE_PERSONAL,
                Tasks_Backend_Factory::SQL
            );
            Egwbase_Container::getInstance()->addGrants($containers[$_owner], $_owner, array(
                Egwbase_Container::GRANT_ADMIN
            ));
        }
        return $containers[$_owner];  
    }
    
    /**
     * Returns Class identifier
     *
     * @param string $_egw14Class
     * @return id identifier
     */
    protected static function getClass($_egw14Class)
    {
        static $classes;
        
        $oldclass = strtoupper($_egw14Class);
        
        if (!isset($classes[$oldclass])) {
            $classTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'class'));
            $class = $classTable->fetchRow($classTable->getAdapter()->quoteInto('class LIKE ?', $oldclass));
            if (!$class) {
                $identifier = $classTable->insert(array(
                    'created_by'    => Zend_Registry::get('currentAccount')->account_id,
                    'creation_time' => Zend_Date::now()->getIso(),
                    'class'         => $oldclass
                ));
                $classes[$oldclass] = $identifier;
            } else {
                $classes[$oldclass] = $class->identifier;
            }
            
        }
        return $classes[$oldclass];
    }
    
    /**
     * Returns Status identifier
     *
     * @param string $_egw14Status
     * @return id identifier
     */
    protected static function getStatus($_egw14Status)
    {
        static $stati;
        
        $oldstatus = strtoupper($_egw14Status);
        
        if (!isset($stati[$oldstatus])) {
            $statusTable = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'tasks_status'));
            $status = $statusTable->fetchRow($statusTable->getAdapter()->quoteInto('status LIKE ?', $oldstatus));
            if (!$status) {
                $identifier = $statusTable->insert(array(
                    'created_by'    => Zend_Registry::get('currentAccount')->account_id,
                    'creation_time' => Zend_Date::now()->getIso(),
                    'status'         => $oldstatus
                ));
                $stati[$oldstatus] = $identifier;
            } else {
                $stati[$oldstatus] = $status->identifier;
            }
            
        }
        return $stati[$oldstatus];
    }
    
    /**
     * Convertes an infolog to a task
     *
     * @param Zend_Db_Table_Row $_infolog
     * @retrun Tasks_Task Task
     */
    protected static function infolog2Task($_infolog)
    {
        $Task = self::$_mapping;
        
        foreach (self::$_mapping as $TaskKey => $InfoKey) {
            if (!$InfoKey) continue;
            
            // Date conversions
            if (in_array($InfoKey, array('info_datemodified','info_datecompleted', 'info_startdate'))) {
                if ((int)$_infolog->$InfoKey > 0) {
                    $_infolog->$InfoKey = new Zend_Date($_infolog->$InfoKey, Zend_Date::TIMESTAMP); 
                } else {
                    unset ($_infolog->$InfoKey);
                    $Task[$TaskKey] = NULL;
                }
            }
            
            // Map fields
            if (isset($_infolog->$InfoKey)) {
                $Task[$TaskKey] = $_infolog->$InfoKey;
            }
        }
        
        // due
        if ($Task['dtstart'] instanceof Zend_Date && (int)$_infolog->info_enddate > 0) {
            $end = new Zend_Date($_infolog->info_enddate, Zend_Date::TIMESTAMP);
            $Task['duration'] = $end->sub($Task['dtstart']);
        }
        
        
        //$Task['identifier'] = self::id2uid($Task['identifier']);      // uid
        unset($Task['identifier']);
        $Task['class'] = self::getClass($Task['class']);              // class
        $Task['status'] = self::getStatus($Task['status']);           // status
        $Task['organizer'] = $Task['organizer'] ? $Task['organizer'] : $Task['created_by'];
        
        //error_log(print_r($Task,true));
        return new Tasks_Task($Task, true);
    }
    
}