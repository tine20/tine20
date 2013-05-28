<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  State
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 *  State filter class
 * 
 * @package     Tinebase
 * @subpackage  State 
 */
class Tinebase_Model_StateFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';

    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Tinebase_Model_State';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'state_id' => array('filter' => 'Tinebase_Model_Filter_Text'),
        'user_id'  => array('filter' => 'Tinebase_Model_Filter_Id'),
    );
}
