<?php
/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Asterisk Meetme Filter Class
 * @package Voipmanager
 */
class Voipmanager_Model_Asterisk_MeetmeFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_applicationName = 'Voipmanager';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Voipmanager_Model_Asterisk_Meetme';
    
    protected $_filterModel = array(
        'query'     => array(
            'filter' => 'Tinebase_Model_Filter_Query', 
            'options' => array(
                'fields' => array('confno', 'pin', 'adminpin')
            )
        ),
        'confno'    => array('filter' => 'Tinebase_Model_Filter_Text')
    );
}