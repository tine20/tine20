<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 */

/**
 * class Tinebase_EmailUser
 * 
 * Email User Settings Managing for dbmail attributes
 * 
 * @package    Tinebase
 * @subpackage EmailUser
 */
abstract class Tinebase_EmailUser_Abstract
{
    /**
     * email user config
     * 
     * @var array 
     */
    protected $_config = array();
    
    /**
     * get new email user
     * 
     * @param Tinebase_Model_FullUser $_user
     * @return Tinebase_Model_EmailUser
     */
    public function getNewUser(Tinebase_Model_FullUser $_user)
    {
        $userId = $_user->accountLoginName;
        
        if (isset($this->_config['domain']) && ! empty($this->_config['domain'])) {
            $userId .= '@' . $this->_config['domain'];
        }
        
        $result = new Tinebase_Model_EmailUser(array(
            'emailUserId' => $userId
        ));
        
        return $result;
    }
    
    /**
     * converts raw data from adapter into a single record / do mapping
     *
     * @param  array $_data
     * @return Tinebase_Record_Abstract
     */
    protected function _rawDataToRecord(array $_rawdata)
    {
        $data = array();
        foreach ($_rawdata as $key => $value) {
            $keyMapping = array_search($key, $this->_userPropertyNameMapping);
            if ($keyMapping !== FALSE) {
                switch($keyMapping) {
                    default: 
                        $data[$keyMapping] = $value;
                        break;
                }
            }
        }
        
        return new Tinebase_Model_EmailUser($data, true);
    }
    
    /**
     * returns array of raw dbmail data
     *
     * @param  Tinebase_Model_EmailUser $_user
     * @return array
     */
    protected function _recordToRawData(Tinebase_Model_EmailUser $_user)
    {
        $data = array();
        foreach ($_user as $key => $value) {
            $property = array_key_exists($key, $this->_userPropertyNameMapping) ? $this->_userPropertyNameMapping[$key] : false;
            if ($property && ! in_array($key, $this->_readOnlyFields)) {
                switch ($key) {
                    case 'emailPassword':
                        if ($this->_config['encryptionType'] == 'md5') {
                            $data[$property] = md5($value);
                        } else {
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . '  encryptionType not supported!');
                        }
                        break;
                    default:
                        $data[$property] = $value;
                }
            }
        }
        
        $data['client_idnr'] = $this->_clientId;
        $data['encryption_type'] = $this->_config['encryptionType'];
        
        return $data;
    }
    
    /**
     * convert some string to absolute int with crc32 and abs
     * 
     * @param $_string
     * @return integer
     */
    protected function _convertToInt($_string)
    {
        return abs(crc32($_string));
    }
}  
