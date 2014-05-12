<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold data representing one node in the tree
 * 
 * @package     Filemanager
 * @subpackage  Model
 */
class Filemanager_Model_DownloadLink extends Tinebase_Record_Abstract
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
    protected $_application = 'Filemanager';
    
    /**
     * list of zend validator
     *
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'node_id'               => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'url'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true), // non-persistent
        'expiry_time'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'password'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'access_count'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        // modlog information
        'created_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'seq'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );
    
    /**
     * (non-PHPdoc)
     * @see Tinebase/Record/Tinebase_Record_Abstract#setFromArray($_data)
     */
    public function setFromArray(array $_data)
    {
        parent::setFromArray($_data);
        
        // always set url here (or is there a better place?)
        if ($this->getId()) {
            $this->url = Tinebase_Core::getHostname() . '/files/' . $this->getId();
        }
    }
    
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
        'Tinebase_Model_User' => array('created_by', 'last_modified_by'),
    );
    /**
     * name of fields containing datetime or an array of datetime information
     *
     * @var array list of datetime fields
    */
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time',
        'expiry_time'
    );
}
