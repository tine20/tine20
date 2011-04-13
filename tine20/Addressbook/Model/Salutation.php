<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold salutation data
 * 
 * @package     Addressbook
 */
class Addressbook_Model_Salutation extends Tinebase_Record_Abstract
{
    /**
     * male gender
     */
    const GENDER_MALE = 'male';
    
    /**
     * female gender
     */
    const GENDER_FEMALE = 'female';
    
    /**
     * other gender
     */
    const GENDER_OTHER = 'other';
    
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
    protected $_application = 'Addressbook';
    
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        'name'                  => array('StringTrim'),
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'name'                  => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'gender'                => array('InArray' => array(self::GENDER_MALE, self::GENDER_FEMALE, self::GENDER_OTHER)),
        'image_path'            => array(Zend_Filter_Input::ALLOW_EMPTY => false)
    );    

    /**
     * fields to translate
     *
     * @var array
     */
    protected $_toTranslate = array(
        'name'
    );        
}
