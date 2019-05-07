<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * class Tinebase_Model_Config
 * 
 * @package     Tinebase
 * @subpackage  Record
 *
 * @property string         name
 * @property string         value
 */
class Tinebase_Model_Config extends Tinebase_Record_Abstract 
{
    const NOTSET = '###NOTSET###';

    const SOURCE_FILE     = 'FILE';
    const SOURCE_DB       = 'DB';
    const SOURCE_DEFAULT  = 'DEFAULT';

    /**
     * identifier
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
     * record validators
     *
     * @var array
     */
    protected $_validators = array(
        // config table fields
        'id'                    => array('allowEmpty' => true ),
        'application_id'        => array('presence' => 'required', 'allowEmpty' => false, 'Alnum' ),
        'name'                  => array('presence' => 'required', 'allowEmpty' => false ),
        'value'                 => array('presence' => 'required', 'allowEmpty' => true ),

        // virtual fields from definition
        'label'                 => array('allowEmpty' => true ),
        'description'           => array('allowEmpty' => true ),
        'type'                  => array('allowEmpty' => true ),
        'options'               => array('allowEmpty' => true ),
        'clientRegistryInclude' => array('allowEmpty' => true ),
        'setByAdminModule'      => array('allowEmpty' => true ),
        'setBySetupModule'      => array('allowEmpty' => true ),
        'default'               => array('allowEmpty' => true ),

        // source of config, as file config's can't be overwritten by db
        'source'                => array(
            Zend_Filter_Input::ALLOW_EMPTY => true,
            array('InArray', array(self::SOURCE_FILE, self::SOURCE_DB, self::SOURCE_DEFAULT))
        ),
    );
    
}
