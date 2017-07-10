<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * defines the datatype for one application
 * 
 * @package     Tinebase
 * @subpackage  Record
 *
 * @property    string  $id
 * @property    string  $name
 * @property    string  $status
 * @property    string  $version
 * @property    array   $tables
 * @property    string  $order
 * @property    array   $state
 */
class Tinebase_Model_Application extends Tinebase_Record_Abstract
{
    /**
     * @var string
     */
    const STATE_REPLICATION_MASTER_ID = 'replicationMasterId';
    const STATE_FILESYSTEM_ROOT_SIZE = 'filesystemRootSize';
    const STATE_FILESYSTEM_ROOT_REVISION_SIZE = 'filesystemRootRevisionSize';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'recordName'        => 'RoleMember',
        'recordsName'       => 'RoleMembers', // ngettext('RoleMember', 'RoleMembers', n)
        'hasRelations'      => FALSE,
        'hasCustomFields'   => FALSE,
        'hasNotes'          => FALSE,
        'hasTags'           => FALSE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => FALSE,
        'createModule'      => FALSE,

        'titleProperty'     => 'name',
        'appName'           => 'Tinebase',
        'modelName'         => 'RoleMember',

        'fields' => array(
            'name'              => array(
                'label'             => 'Name', //_('Name')
                'type'              => 'string',
                'queryFilter'       => TRUE,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'inputFilters'      => array('Zend_Filter_StringTrim' => NULL),
            ),
            'version'           => array(
                'label'             => 'Version', //_('Version')
                'type'              => 'string',
                'queryFilter'       => TRUE,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'inputFilters'      => array('Zend_Filter_StringTrim' => NULL),
            ),
            'status'            => array(
                'label'             => 'Status', //_('Status')
                'type'              => 'string',
                'queryFilter'       => TRUE,
                'validators'        => array(array('InArray', array('enabled', 'disabled'))),
            ),
            'order'             => array(
                'label'             => 'Order', //_('Order')
                'type'              => 'string',
                'queryFilter'       => TRUE,
                'validators'        => array('Digits', 'presence' => 'required'),
            ),
            'tables'            => array(
                'label'             => 'Tables', //_('Tables')
                'type'              => 'string',
                'queryFilter'       => TRUE,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true)
            ),
            'state'             => array(
                'label'             => 'State', //_('State')
                'type'              => 'json',
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true)
            ),
        )
    );
    
    /**
     * converts a int, string or Tinebase_Model_Application to an accountid
     *
     * @param   int|string|Tinebase_Model_Application $_applicationId the app id to convert
     * @return  int
     * @throws  Tinebase_Exception_InvalidArgument
     */
    static public function convertApplicationIdToInt($_applicationId)
    {
        if($_applicationId instanceof Tinebase_Model_Application) {
            if(empty($_applicationId->id)) {
                throw new Tinebase_Exception_InvalidArgument('No application id set.');
            }
            $applicationId = $_applicationId->id;
        } elseif (!ctype_digit($_applicationId) && is_string($_applicationId) && strlen($_applicationId) != 40) {
            $applicationId = Tinebase_Application::getInstance()->getApplicationByName($_applicationId)->getId();
        } else {
            $applicationId = $_applicationId;
        }
        
        return $applicationId;
    }
        
    /**
     * returns applicationname
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }    
    
    /**
     * return the version of the application
     *
     * @return  array with minor and major version
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function getMajorAndMinorVersion()
    {
        if (empty($this->version)) {
            throw new Tinebase_Exception_InvalidArgument('No version set.');
        }

        if (strpos($this->version, '.') === false) {
            $minorVersion = 0;
            $majorVersion = $this->version;
        } else {
            list($majorVersion, $minorVersion) = explode('.', $this->version);
        }

        return array('major' => $majorVersion, 'minor' => $minorVersion);
    }

    /**
     * get major app version
     *
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function getMajorVersion()
    {
        $versions = $this->getMajorAndMinorVersion();
        return $versions['major'];
    }
}
