<?php
/**
 * class to hold Division data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        add Division status table
 */

/**
 * class to hold Division data
 *
 * @package     Sales
 */
class Sales_Model_Division extends Tinebase_Record_Abstract
{
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
        'recordName'        => 'Division',
        'recordsName'       => 'Divisions', // ngettext('Division', 'Divisions', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => FALSE,
        'hasNotes'          => FALSE,
        'hasTags'           => FALSE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => FALSE,
        'createModule'      => TRUE,
        'containerProperty' => NULL,
    
        'titleProperty'     => 'title',
        'appName'           => 'Sales',
        'modelName'         => 'Division',
    
        'fields'            => array(
            'title' => array(
                'label'   => 'Title', // _('Title')
                'type'    => 'string',
                'duplicateCheckGroup' => 'title',
                'queryFilter' => TRUE,
            ),
        )
    );
}
