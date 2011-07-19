<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class representing one node path
 * 
 * @package     Tinebase
 * @subpackage  Model
 * @property    string                      type
 * @property    string                      flat
 * @property    Tinebase_Model_Application  application
 * @property    Tinebase_Model_Container    container
 * @property    Tinebase_Model_FullUser     user
 */
class Tinebase_Model_Tree_Node_Path extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the field which 
     * represents the identifier
     * 
     * @var string
     */
    protected $_identifier = 'flat';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array (
        'type'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'flat'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'application'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'container'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'user'			    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );
}
