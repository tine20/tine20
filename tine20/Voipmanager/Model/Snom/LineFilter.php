<?php
/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Phone Filter Class
 * @package Voipmanager
 */
class Voipmanager_Model_Snom_LineFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Voipmanager';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Voipmanager_Model_Snom_Line';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'snomphone_id'         => array('filter' => 'Tinebase_Model_Filter_Id'),
    );
}
