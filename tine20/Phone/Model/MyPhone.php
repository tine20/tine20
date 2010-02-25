<?php
/**
 * class to hold myPhone data
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: MyPhone.php 10601 2009-09-27 13:09:59Z l.kneschke@metaways.de $
 *
 */

/**
 * class to hold myPhone data
 * 
 * @package     Phone
 */
class Phone_Model_MyPhone extends Tinebase_Record_Abstract
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
    protected $_application = 'Phone';
    
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id' 			        => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'template_id'           => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'settings'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'lines'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'description'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );
    
    /**
     * converts a int, string or Phone_Model_MyPhone to an phone id
     *
     * @param int|string|Phone_Model_MyPhone $_phoneId the phone id to convert
     * @return int
     * @throws  Phone_Exception_InvalidArgument
     */
    static public function convertMyPhoneIdToInt($_phoneId)
    {
        if ($_phoneId instanceof Phone_Model_MyPhone) {
            if (empty($_phoneId->id)) {
                throw new Phone_Exception_InvalidArgument('no phone id set');
            }
            $id = (string) $_phoneId->id;
        } else {
            $id = (string) $_phoneId;
        }
        
        if ($id == '') {
            throw new Phone_Exception_InvalidArgument('phone id can not be 0');
        }

        return $id;
    }

}