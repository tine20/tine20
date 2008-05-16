<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * class Tinebase_Model_Image
 * 
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_Config extends Tinebase_Record_Abstract 
{    
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
        'id'                => array('allowEmpty' => true ),
        'application_id'    => array('presence' => 'required', 'allowEmpty' => false, 'Alnum' ),
        'name'              => array('presence' => 'required', 'allowEmpty' => false ),
        'value'             => array('presence' => 'required', 'allowEmpty' => false ),        
    );
    
} // end of Tinebase_Model_Config
