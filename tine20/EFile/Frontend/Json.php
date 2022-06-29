<?php
/**
 * Tine 2.0
 * 
 * @package     EFile
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 *
 * This class handles all Json requests for the EFile application
 *
 * @package     EFile
 * @subpackage  Frontend
 */
class EFile_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    protected $_configuredModels = [
        EFile_Model_FileMetadata::MODEL_NAME_PART
    ];
    
    /**
     * the constructor
     */
    public function __construct()
    {
        $this->_applicationName = EFile_Config::APP_NAME;
    }
    
    /**
     * @return array
     */
    public function getRegistryData()
    {
        $data = parent::getRegistryData();
        $data['EFileNodeLIVR'] = EFile_Config::getNodeLIVR();

        return $data;
    }
}
