<?php
/**
 * class to hold location data
 * 
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Product.php 2531 2008-05-18 07:52:12Z nelius_weiss $
 *
 */

/**
 * class to hold location data
 * 
 * @package     Voipmanager Management
 */
class Voipmanager_Model_Location extends Tinebase_Record_Abstract
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
    protected $_application = 'Voipmanager';
    
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        '*'                     => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                        => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'name'                      => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'description'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'firmware_interval'			=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'firmware_status'			=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'update_policy'				=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'setting_server'			=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'admin_mode'				=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'admin_mode_password'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'ntp_server'				=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'webserver_type'			=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'https_port'				=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'http_user'					=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'http_pass'					=> array(Zend_Filter_Input::ALLOW_EMPTY => true)
    );

    /**
     * converts a int, string or Voipmanager_Model_Location to an location id
     *
     * @param int|string|Voipmanager_Model_Location $_locationId the location id to convert
     * @return int
     */
    static public function convertLocationIdToInt($_locationId)
    {
        if ($_locationId instanceof Voipmanager_Model_Location) {
            if (empty($_locationId->id)) {
                throw new Exception('no location id set');
            }
            $id = (string) $_locationId->id;
        } else {
            $id = (string) $_locationId;
        }
        
        if ($id == '') {
            throw new Exception('location id can not be 0');
        }
        
        return $id;
    }

}