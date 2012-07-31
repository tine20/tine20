<?php
/**
 * class to hold FAQ data
 * 
 * @package     SimpleFAQ
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Patrick Ryser <patrick.ryser@gmail.com>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * SimpleFAQ Class to hold faq data
 * 
 * @package     SimpleFAQ
 */
class SimpleFAQ_Model_Faq extends Tinebase_Record_Abstract
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
    protected $_application = 'SimpleFAQ';

	/**
     * if foreign Id fields should be resolved on search and get from json
     * should have this format: 
     *     array('Calendar_Model_Contact' => 'contact_id', ...)
     * or for more fields:
     *     array('Calendar_Model_Contact' => array('contact_id', 'customer_id), ...)
     * (e.g. resolves contact_id with the corresponding Model)
     * 
     * @var array
     */
    protected static $_resolveForeignIdFields = array(
        'Tinebase_Model_User' => array('created_by', 'last_modified_by')
    ); 

     /**
     * list of zend validator
     *
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
     protected $_validators = array(
         'id'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true,),
         'container_id'         => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
         'faqstatus_id'         => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required', 'default' => 1),
         'faqtype_id'           => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required', 'default' => 1),
         'answer'               => array(Zend_Filter_Input::ALLOW_EMPTY => true,),
         'question'             => array(Zend_Filter_Input::ALLOW_EMPTY => true,),
         'created_by'           => array(Zend_Filter_Input::ALLOW_EMPTY => true,),
         'creation_time'        => array(Zend_Filter_Input::ALLOW_EMPTY => true,),
         'last_modified_by'     => array(Zend_Filter_Input::ALLOW_EMPTY => true,),
         'last_modified_time'   => array(Zend_Filter_Input::ALLOW_EMPTY => true,),
         'is_deleted'           => array(Zend_Filter_Input::ALLOW_EMPTY => true,),
         'deleted_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true,),
         'deleted_by'           => array(Zend_Filter_Input::ALLOW_EMPTY => true,),
         'tags'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true,),
         'relations'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
         'notes'                => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
     );

     /**
     * datetime fields
     *
     * @var array
     */
     protected $_datetimeFields = array (
        'creation_time',
        'last_modified_time',
        'deleted_time'
     );

     /**
     * fills a record from json data
     *
     * @param string $_data json encoded data
     * @return void
     */
     public function setFromJson($_data)
     {
        parent::setFromJson($_data);

        // do something here if you like
     }
}
