<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
class Tasks_Setup_MigrateFromTine14 
{

    /**
     * Mapping Tasks Model / Tine 1.4 Infolog Table
     *
     * @var array
     */
    protected static $_mapping = array(
        // tine record fields
        'container'            => '',
        'created_by'           => 'info_owner',
        'creation_time'        => 'info_datemodified',
        'last_modified_by'     => 'info_modifier',
        'last_modified_time'   => 'info_datemodified',
        'is_deleted'           => '',
        'deleted_time'         => NULL,
        'deleted_by'           => '',
        // task only fields
        'identifier'           => 'info_id',
        'percent'              => 'info_percent',
        'completed'            => 'info_datecompleted',
        'due'                  => 'info_enddate',
        // ical common fields
        'class'                => 'info_access',
        'description'          => 'info_des',
        'geo'                  => '',
        'location'             => 'info_location',
        'organizer'            => 'info_responsible',
        'priority'             => 'info_priority',
        'status'               => 'info_status',
        'summary'             => 'info_subject',
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
        'duration'              => 'info_planned_time',
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

        $db = Tinebase_Core::getDb();
        $stmt = $db->query($db->select()
            ->from('egw_infolog')
        );
        
        while ($infolog = $stmt->fetchObject()) {
            $Task = self::$_mapping;
            
            foreach (array('info_datemodified', 'info_datecompleted', 'info_enddate', 'info_startdate' ) as $datefield) {
                if ((int)$infolog->$datefield == 0) continue;
                $infolog->$datefield = new Zend_Date($infolog->$datefield, Zend_Date::TIMESTAMP);
            }
            
            foreach (self::$_mapping as $TaskKey => $InfoKey) {
                if (!$InfoKey) continue;
                
                // Map fields
                if (isset($infolog->$InfoKey)) {
                    $Task[$TaskKey] = $infolog->$InfoKey;
                }
            }
            
            //$Task['identifier'] = self::id2uid($Task['identifier']);      // uid
            unset($Task['identifier']);
    
            $Task['class']     = self::getClass($Task['class']);
            $Task['status']    = self::getStatus($Task['status']);
            $Task['container'] = self::getOwnersContainer($infolog->info_owner);
            $Task['organizer'] = $Task['organizer'] ? $Task['organizer'] : $Task['created_by'];
            
            error_log(print_r($Task,true));
            try {
                $Task20 = new Tasks_Model_Task(NULL, true, true);
                $Task20->setFromArray($Task);
                
            } catch (Tinebase_Exception_Record_Validation $e) {
                $validation_errors = $Task20->getValidationErrors(); 
                Tinebase_Core::getLogger()->debug(
                    'Could not migrate Infolog with info_id ' . $infolog->info_id . "\n" . 
                    'Tasks_Setup_MigrateFromTine14::infolog2Task: ' . $e->getMessage() . "\n" .
                    "Tasks_Model_Task::validation_errors: \n" .
                    print_r($validation_errors,true));
                    continue;
            }
            $tasksBackend->create($Task20);
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
     * @todo Make sure we use same containers in calendar and tasks!
     * ??? what about loose coupling in this case???
     * 
     * @param int $_owner
     * @return int Tinebase_Container::id
     */
    protected static function getOwnersContainer($_owner)
    {
        static $containers = array();
        
        if (!isset($containers[$_owner])) {
            $containers[$_owner] = Tinebase_Container::getInstance()->addContainer(
                'Tasks',
                'My Tasks (from egw 1.4 migration)',
                Tinebase_Model_Container::TYPE_PERSONAL,
                Tasks_Backend_Factory::SQL
            );
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
            $classTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'class'));
            $class = $classTable->fetchRow($classTable->getAdapter()->quoteInto('class LIKE ?', $oldclass));
            if (!$class) {
                $identifier = $classTable->insert(array(
                    'created_by'    => Tinebase_Core::getUser()->getId(),
                    'creation_time' => Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
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
            $statusTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'tasks_status'));
            $status = $statusTable->fetchRow($statusTable->getAdapter()->quoteInto('status LIKE ?', $oldstatus));
            if (!$status) {
                $identifier = $statusTable->insert(array(
                    'created_by'    => Tinebase_Core::getUser()->getId(),
                    'creation_time' => Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
                    'status'         => $oldstatus
                ));
                $stati[$oldstatus] = $identifier;
            } else {
                $stati[$oldstatus] = $status->identifier;
            }
            
        }
        return $stati[$oldstatus];
    }
    
}