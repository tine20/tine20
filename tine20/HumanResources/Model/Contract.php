<?php
/**
 * Tine 2.0

 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Contract data
 *
 * @package     HumanResources
 * @subpackage  Model
 */
class HumanResources_Model_Contract extends Tinebase_Record_Abstract
{
    /**
     * holds the configuration object (must be set in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject;
    
    /**
     * Holds the model configuration
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'recordName'      => 'Contract', // ngettext('Contract', 'Contracts', n)
        'recordsName'     => 'Contracts',
        'hasRelations'    => FALSE,
        'hasCustomFields' => FALSE,
        'hasNotes'        => FALSE,
        'hasTags'         => FALSE,
        'modlogActive'    => TRUE,
        'containerProperty' => NULL,
        'createModule'    => FALSE,
        'isDependent'     => TRUE,
        'titleProperty'   => 'start_date',
        'appName'         => 'HumanResources',
        'modelName'       => 'Contract',
        
        'fields'          => array(
            'employee_id'       => array(
                'label'      => 'Employee',    // _('Employee')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                'type'       => 'record',
                'sortable'   => FALSE,
                'config' => array(
                    'appName'     => 'HumanResources',
                    'modelName'   => 'Employee',
                    'idProperty'  => 'id',
                    'isParent'    => TRUE
                )
            ),
            'start_date'        => array(
                'label'      => 'Start Date',    // _('Start Date')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type'       => 'date',
                'sortable'   => FALSE,
                 'default'    => 'now',
                 'showInDetailsPanel' => TRUE
            ),
            'end_date'          => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
                'label'   => 'End Date',    // _('End Date')
                'type'    => 'date',
                'sortable'   => FALSE,
                'showInDetailsPanel' => TRUE
            ),
            'vacation_days'     => array(
                'label'   => 'Vacation Days',    // _('Vacation Days')
                'type'    => 'integer',
                'default' => 27,
                'queryFilter' => TRUE,
                'sortable'   => FALSE,
                'showInDetailsPanel' => TRUE
            ),
            'feast_calendar_id' => array(
                'label' => 'Feast Calendar',    // _('Feast Calendar')
                'type'  => 'container',
                'config' => array(
                    'appName'   => 'Calendar',
                    'modelName' => 'Event',
                ),
                'sortable'   => FALSE,
                'showInDetailsPanel' => TRUE
            ),
            'workingtime_json'  => array(
                'label'   => 'Workingtime', // _('Workingtime')
                'default' => '{"days": [8,8,8,8,8,0,0]}',
                'sortable'   => FALSE,
                'showInDetailsPanel' => TRUE
            ),
            'is_editable' => array(
                'label' => NULL,
                'type' => 'virtual',
                'config' => array(
                    'sortable'   => FALSE,
                    'type' => 'boolean',
                    'function' => array('HumanResources_Controller_Contract' => 'getEditableState'),
                )
            )
        )
    );
    
    /**
     * resolves workingtime json
     * 
     * @return mixed
     */
    public function getWorkingTimeJson()
    {
        return json_decode($this->__get('workingtime_json'));
    }
    
    /**
     * sets workingtime_json as json
     * 
     * @param mixed $workingTimeJson
     */
    public function setWorkingTimeJson($workingTimeJson)
    {
        $this->workingtime_json = json_encode($workingTimeJson);
    }
    
    /**
     * returns the weekly working hours of this record as an integer
     * 
     * @return integer
     */
    public function getWeeklyWorkingHours()
    {
        $json = $this->getWorkingTimeJson();
        $whours = 0;
        foreach($json->days as $index => $hours) {
            $whours += $hours;
        }
        
        return $whours;
    }
}
