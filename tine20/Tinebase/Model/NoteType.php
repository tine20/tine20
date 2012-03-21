<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Notes
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 * @todo remove icon field (first check if it is used somewhere) -> we should use the icon_class instead
 */

/**
 * defines the datatype for note types
 * 
 * @package     Tinebase
 * @subpackage  Notes
 */
class Tinebase_Model_NoteType extends Tinebase_Record_Abstract
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
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        'name'              => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                     => array('Alnum', 'allowEmpty' => true),
    
        'name'                   => array('presence' => 'required', 'allowEmpty' => false),    
        'icon'                   => array('allowEmpty' => true),
        'icon_class'             => array('allowEmpty' => true),
    
        'description'            => array('allowEmpty' => true),    
        'is_user_type'           => array('allowEmpty' => true),    
    );
    
    /**
     * fields to translate
     *
     * @var array
     */
    protected $_toTranslate = array(
        'name',
        'description'
    );
    
}
