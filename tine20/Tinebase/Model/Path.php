<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * class Tinebase_Model_Path
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_Path extends Tinebase_Record_Abstract 
{
    /**
     * key in $_validators/$_properties array for the field which
     *   represents the identifier
     *
     * @var string
     */
    protected $_identifier = 'id';

    /**
     * record validators
     *
     * @var array
     */
    protected $_validators = array(
        'id'                => array('allowEmpty' => TRUE),
        'record_id'         => array('allowEmpty' => TRUE),
        'path'              => array('allowEmpty' => TRUE),
        'shadow_path'       => array('allowEmpty' => TRUE),
        'creation_time'     => array('allowEmpty' => TRUE),
    );
    
    /**
     * datetime fields
     *
     * @var array
     */
    protected $_datetimeFields = array(
        'creation_time',
    );
}
