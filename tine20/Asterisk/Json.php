<?php
/**
 * Tine 2.0
 * @package     Asterisk Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Json.php 2131 2008-04-24 10:20:09Z ph_il $
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the Asterisk Management application
 *
 * @package     Asterisk Management
 */
class Asterisk_Json extends Tinebase_Application_Json_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_appname = 'Asterisk';

    
    /**
     * get snom phones
     *
     * @param string $sort
     * @param string $dir
     * @return array
     */
    public function getPhones($sort, $dir, $query)
    {     
  
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Asterisk_Controller::getInstance()->getPhones($sort, $dir, $query)) {
            $result['results']      = $rows->toArray();
            $result['totalcount']   = count($result['results']);
        }

        return $result;    
    }
    
    
   /**
     * get one phone identified by phoneId
     *
     * @param int $phoneId
     * @return array
     */
    public function getPhoneById($phoneId)
    {
        $result = array(
            'success'   => true
        );

        $phone = Asterisk_Controller::getInstance()->getPhoneById($phoneId);
        
        $result = $phone->toArray();        
        return $result;
    }    
    
    
    /**
     * delete multiple phones
     *
     * @param array $_phoneIDs list of phoneId's to delete
     * @return array
     */
    public function deletePhones($_phoneIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        $phoneIds = Zend_Json::decode($_phoneIds);
        
        Asterisk_Controller::getInstance()->deletePhone($phoneIds);

        return $result;
    }    
    
    
    /**
     * save one phone
     *
     * if $phoneData['id'] is empty the phone gets added, otherwise it gets updated
     *
     * @param string $phoneData a JSON encoded array of phone properties
     * @return array
     */
    public function savePhone($phoneData)
    {
        $phoneData = Zend_Json::decode($phoneData);
        Zend_Registry::get('logger')->debug(print_r($phoneData,true));
        
        // unset if empty
        if (empty($phoneData['id'])) {
            unset($phoneData['id']);
        }

        //Zend_Registry::get('logger')->debug(print_r($phoneData,true));
        $phone = new Asterisk_Model_Phone();
        $phone->setFromArray($phoneData);
        
        if (empty($phone->id)) {
            $phone = Asterisk_Controller::getInstance()->addPhone($phone);
        } else {
            $phone = Asterisk_Controller::getInstance()->updatePhone($phone);
        }
        $phone = $this->getPhoneById($phone->getId());
        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $phone
        ); //$phone->toArray());
        
        
        return $result;
         
    }     
        
        
    /**
     * get snom config
     *
     * @param string $sort
     * @param string $dir
     * @return array
     */
    public function getConfig($sort, $dir, $query)
    {     
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Asterisk_Controller::getInstance()->getConfig($sort, $dir, $query)) {
            $result['results']      = $rows->toArray();
            $result['totalcount']   = count($result['results']);
        }

        return $result;    
    }        
    
   /**
     * get one config identified by configId
     *
     * @param int $configId
     * @return array
     */
    public function getConfigById($configId)
    {
        $result = array(
            'success'   => true
        );

        $config = Asterisk_Controller::getInstance()->getConfigById($configId);
        
        $result = $config->toArray();        
        return $result;
    }      
    
        
        
        
        
    /**
     * get snom software
     *
     * @param string $sort
     * @param string $dir
     * @return array
     */
    public function getSoftware($sort, $dir, $query)
    {     
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Asterisk_Controller::getInstance()->getSoftware($sort, $dir, $query)) {
            $result['results']      = $rows->toArray();
            $result['totalcount']   = count($result['results']);
        }

        return $result;    
    }        
    
    
   /**
     * get one software identified by softwareId
     *
     * @param int $softwareId
     * @return array
     */
    public function getSoftwareById($softwareId)
    {
        $result = array(
            'success'   => true
        );

        $software = Asterisk_Controller::getInstance()->getSoftwareById($softwareId);
        
        $result = $software->toArray();        
        return $result;
    }         

    /**
     * add/update software
     *
     * if $softwareData['id'] is empty the software gets added, otherwise it gets updated
     *
     * @param string $phoneData a JSON encoded array of software properties
     * @return array
     */
    public function saveSoftware($softwareData)
    {
        $softwareData = Zend_Json::decode($softwareData);
        Zend_Registry::get('logger')->debug(print_r($softwareData,true));
        
        // unset if empty
        if (empty($softwareData['id'])) {
            unset($softwareData['id']);
        }

        //Zend_Registry::get('logger')->debug(print_r($phoneData,true));
        $software = new Asterisk_Model_Software();
        $software->setFromArray($softwareData);
        
        if (empty($software->getId())) {
            $software = Asterisk_Controller::getInstance()->addSoftware($software);
        } else {
            $software = Asterisk_Controller::getInstance()->updateSoftware($software);
        }
        //$software = $this->getSoftware($software->getId());
        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $software->toArray()
        ); //$phone->toArray());
        
        
        return $result;
         
    }     
        
    
}