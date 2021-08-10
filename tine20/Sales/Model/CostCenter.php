<?php
/**
 * class to hold CostCenter data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        add CostCenter status table
 */

/**
 * class to hold CostCenter data
 *
 * @package     Sales
 */
class Sales_Model_CostCenter extends Tinebase_Record_Abstract
{
    public const MODEL_NAME_PART = 'CostCenter';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
    
    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'recordName'        => 'Cost Center', // gettext('GENDER_Cost Center')
        'recordsName'       => 'Cost Centers', // ngettext('Cost Center', 'Cost Centers', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => FALSE,
        'hasNotes'          => FALSE,
        'hasTags'           => FALSE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => FALSE,
        'createModule'      => TRUE,
        'containerProperty' => NULL,
        
        'titleProperty'     => 'remark',
        'appName'           => 'Sales',
        'modelName'         => 'CostCenter',
        
        'fields'            => array(
            'number' => array(
                'label' => 'Number', //_('Number')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
                'type'  => 'string',
                'duplicateCheckGroup' => 'number',
                
            ),
            'remark' => array(
                'label'   => 'Remark', // _('Remark')
                'queryFilter' => TRUE,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true)
            ),
        )
    );

    /**
     * returns the title of the record
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->number . ' - ' . $this->remark;
    }
}
