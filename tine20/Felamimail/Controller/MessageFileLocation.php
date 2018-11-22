<?php
/**
 * MessageFileLocation controller for Felamimail application
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * MessageFileLocation controller class for Felamimail application
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_MessageFileLocation extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = 'Felamimail';
        $this->_modelName = Felamimail_Model_MessageFileLocation::class;
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName,
            'tableName' => 'felamimail_message_filelocation',
            'modlogActive' => true
        ));
        $this->_purgeRecords = false;
    }

    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_MessageFileLocation
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Felamimail_Controller_MessageFileLocation
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * @param string $referenceString
     * @return Tinebase_Record_RecordSet
     */
    public function getLocationsByReference($referenceString)
    {
        if (strpos(' ', $referenceString) !== false) {
            $references = explode(' ', $referenceString);
        } else {
            $references = [$referenceString];
        }

        $hashedReferences = array_map('sha1', $references);
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Felamimail_Model_MessageFileLocation::class, [
                ['field' => 'message_id_hash', 'operator' => 'in', 'value' => $hashedReferences]
            ]
        );
        $locations = $this->search($filter);

        return $locations;
    }
}
