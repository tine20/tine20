<?php
/**
 * class to hold softwareImage data
 * 
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Software.php 2950 2008-06-18 10:30:20Z lkneschke $
 *
 */

/**
 * class to hold softwareImage data
 * 
 * @package     Voipmanager Management
 */
class Voipmanager_Model_SoftwareImage extends Tinebase_Record_Abstract
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
        'id'						=> array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'software_id'				=> array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'model'                     => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'softwareimage'		        => array(Zend_Filter_Input::ALLOW_EMPTY => false)
    );

    /**
     * converts a int, string or Voipmanager_Model_SoftwareImage to an software id
     *
     * @param int|string|Voipmanager_Model_SoftwareImage $_softwareImageId the software id to convert
     * @return int
     */
    static public function convertSoftwareImageIdToInt($_softwareImageId)
    {
        if ($_softwareImageId instanceof Voipmanager_Model_SoftwareImage) {
            if (empty($_softwareImageId->id)) {
                throw new Exception('no softwareImage id set');
            }
            $id = (string) $_softwareImageId->id;
        } else {
            $id = (string) $_softwareImageId;
        }
        
        if ($id == '') {
            throw new Exception('softwareImage id can not be 0');
        }
        
        return $id;
    }

}