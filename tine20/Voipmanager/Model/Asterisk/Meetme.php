<?php
/**
 * class to hold asterisk meetme data
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * class to hold asterisk meetme data
 * 
 * @package     Voipmanager 
 */
class Voipmanager_Model_Asterisk_Meetme extends Tinebase_Record_Abstract
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
    #protected $_filters = array(
    #    '*'                     => 'StringTrim'
    #);
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'confno'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pin'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adminpin'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'members'               => array(Zend_Filter_Input::ALLOW_EMPTY => false, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'starttime'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'endtime'               => array(Zend_Filter_Input::ALLOW_EMPTY => true)
    );
    
    /**
     * name of fields containing datetime or or an array of datetime information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'startime',
        'endtime'
    );
    
    /**
     * converts a int, string or Voipmanager_Model_Asterisk_Meetme to an meetme id
     *
     * @param int|string|Voipmanager_Model_Asterisk_Meetme $_meetmeId the meetme id to convert
     * @return int
     * @throws  Voipmanager_Exception_InvalidArgument
     */
    static public function convertAsteriskMeetmeIdToInt($_meetmeId)
    {
        if ($_meetmeId instanceof Voipmanager_Model_Asterisk_Meetme) {
            if (empty($_meetmeId->id)) {
                throw new Voipmanager_Exception_InvalidArgument('no meetme id set');
            }
            $id = (string) $_meetmeId->id;
        } else {
            $id = (string) $_meetmeId;
        }
        
        if ($id == '') {
            throw new Voipmanager_Exception_InvalidArgument('meetme id can not be 0');
        }
        
        return $id;
    }

}
