<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * class Tinebase_Model_ImportExportDefinition
 * 
 * @package     Tinebase
 * @subpackage  Import
 *
 * @property string id
 * @property string application_id
 * @property string model
 * @property string name
 * @property string label
 * @property string description
 * @property string type
 * @property string plugin
 * @property string plugin_options
 * @property string filename
 * @property bool   favorite
 * @property string icon_class
 * @property int    order
 */
class Tinebase_Model_ImportExportDefinition extends Tinebase_Record_Abstract 
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
    protected $_application = 'Tinebase';

    /**
     * hidden from frontend
     */
    const SCOPE_HIDDEN = 'hidden';

    /**
     * only (?) export multiple records
     */
    const SCOPE_MULTI = 'multi';

    /**
     * only (?) export single records
     */
    const SCOPE_SINGLE = 'single';

    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'application_id'        => array(Zend_Filter_Input::ALLOW_EMPTY => false,   'presence' => 'required'),
        'model'                 => array(Zend_Filter_Input::ALLOW_EMPTY => false,   'presence' => 'required'),
        'name'                  => array(Zend_Filter_Input::ALLOW_EMPTY => false,   'presence' => 'required'),
        'label'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'description'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'type'                  => array(
            Zend_Filter_Input::ALLOW_EMPTY  => false, 
            'presence'                      => 'required',
            array('InArray', array('import', 'export', 'letter'))
        ),
        'favorite'              => array(Zend_Filter_Input::ALLOW_EMPTY => true,    'default' => true),
        'order'                 => array(Zend_Filter_Input::ALLOW_EMPTY => false,   'presence' => 'required', 'default' => 0),
        'icon_class'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'plugin'                => array(Zend_Filter_Input::ALLOW_EMPTY => false,   'presence' => 'required'),
        'scope'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'plugin_options'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'format'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // if filename is set, read file from App/Export(Import)/definitions/filename
        'filename'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // modlog information
        'created_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'seq'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );

    /**
     * name of fields containing datetime or an array of datetime information
     *
     * @var array list of datetime fields
     */    
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time'
    );


    /**
     * get defined filter
     *
     * @TODO: implement
     * - add filterData property
     * - add persistendFilter property
     * - rethink: what to return when no filter is defined? empty filter?
     * - rethink: overwrite or combine filters / have option for that?
     *
     * @return Tinebase_Model_Filter_FilterGroup
     */
    public function getFilter()
    {
        return Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->model);
    }
}
