<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Addressbook initialization
 *
 * @package     Setup
 */
class Addressbook_Setup_DemoData extends Tinebase_Setup_DemoData_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Addressbook_Setup_DemoData
     */
    private static $_instance = NULL;
    
    /**
     * the application name to work on
     * 
     * @var string
     */
    protected $_appName = 'Addressbook';
    
    /**
     * the addresses got from csv
     */
    protected $_addresses = NULL;
    
    /**
     * required apps
     * 
     * @var array
     */
    protected static $_requiredApplications = array('Admin');
    
    /**
     * holds indexes of male images in $this->_images
     * 
     * @var array
     */
    protected $_photosMale = NULL;
    
    /**
     * holds indexes of female images in $this->_images
     * 
     * @var array
     */
    protected $_photosFemale = NULL;
    
    /**
     * the corresponding images to the contacts
     */
    protected $_images = NULL;
    
    protected $_createdContactIndex = 0;
    
    /**
     * the controller
     * 
     * @var Addressbook_Controller_Contact
     */
    protected $_controller;
    
    /**
     * models to work on
     * @var array
     */
    protected $_models = array('contact');
    
    /**
     * the constructor
     */
    private function __construct()
    {
        $this->_controller = Addressbook_Controller_Contact::getInstance();
        $this->_controller->sendNotifications(FALSE);
        $this->_controller->setGeoDataForContacts(FALSE);
    }

    /**
     * the singleton pattern
     *
     * @return Addressbook_Setup_DemoData
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
    
    /**
     * this is required for other applications needing demo data of this application
     * if this returns true, this demodata has been run already
     * 
     * @return boolean
     */
    public static function hasBeenRun()
    {
        $c = Addressbook_Controller_Contact::getInstance();
        
        $f = new Addressbook_Model_ContactFilter(array(
            array('field' => 'url', 'operator' => 'contains', 'value' => 'brillenstobercstober.com'),
        ), 'AND');
        
        return ($c->search($f)->count() > 50) ? true : false;
    }
    
    /**
     * @see Tinebase_Setup_DemoData_Abstract
     * 
     * get csv-record-data and image-data
     * attention: image-line[n] = csv-line[n-1]
     */
    protected function _onCreate()
    {
        $csvFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'DemoData' . DIRECTORY_SEPARATOR . 'out1000.csv';
        $imageFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'DemoData' . DIRECTORY_SEPARATOR . 'base64images.txt';
        if (! (file_exists($csvFile) && file_exists($imageFile))) {
            die('File does not exist!');
        }
        $fhcsv = fopen($csvFile, 'r');
        $fhimages = fopen($imageFile, 'r');
        $i=0;
        
        $femalePhotoIndex = 0;
        $malePhotoIndex   = 0;
        
        $indexes = fgetcsv($fhcsv);
        
        while ($row = fgetcsv($fhcsv)) {
            foreach($row as $index => $field) {
                if ($indexes[$index] == 'gender') {
                    if ($field == 'male') {
                        $isMan = true;
                        $this->_addresses[$i]['salutation'] = 'MR';
                    } else {
                        $isMan = false;
                        $this->_addresses[$i]['salutation'] = 'MS';
                    }
                } else {
                    $this->_addresses[$i][$indexes[$index]] = $field;
                }
            }
            
            // get the base64 encoded photo
            $photo = fgets($fhimages);
            
            // if photo exists, add to photo index
            if (! empty($photo)) {
                if ($isMan) {
                    $this->_photosMale[] = $i;
                } else {
                    $this->_photosFemale[] = $i;
                }
            // if no photo exists, take from photo index
            } else {
                if ($isMan) {
                    $photo = $this->_images[$this->_photosMale[$malePhotoIndex]];
                    $malePhotoIndex++;
                } else {
                    $photo = $this->_images[$this->_photosFemale[$femalePhotoIndex]];
                    $femalePhotoIndex++;
                }
            }
            
            $this->_images[$i] = $photo;
            
            $i++;
        }
        fclose($fhcsv);
        fclose($fhimages);
    }
    
    /**
     * creates a contact and the image, if given
     */
    protected function _createContact($data, $imageData)
    {
        $record = new Addressbook_Model_Contact($data);
        $be = new Addressbook_Backend_Sql();
        $imageData = base64_decode($imageData);
        try {
            $record = $this->_controller->create($record);
            if ($imageData) {
                // @todo We should not use copyrighted/random pictures for demo data
                //$be->_saveImage($record->getId(), $imageData);
            }
        } catch (Exception $e) {
            echo 'Skipping: ' . $data['n_given'] .' ' . $data['n_family'] . ($data['org_name'] ? ' ('.$data['org_name'].') ' : '') . $e->getMessage() . PHP_EOL;
        }
    }
    
    /**
     * creates 700 shared contacts
     */
    protected function _createSharedContacts()
    {
        $count = static::$_createFullData ? 700 : 20;
        
        $this->_createSharedContainer((static::$_en ? 'Customers' : 'Kunden'));
        $cid = $this->_sharedContainer->getId();
        $i = 0;
        while ($i < $count) {
            $data = array_merge($this->_addresses[($this->_createdContactIndex + $i)], array('container_id' => $cid));
            $this->_createContact($data, $this->_images[($this->_createdContactIndex + $i)]);
            $i++;
        }
        $this->_createdContactIndex = $this->_createdContactIndex + $i;
    }
    
    /**
     * creates 100 contacts for pwulf
     */
    protected function _createContactsForPwulf()
    {
        $i=0;
        while ($i < (static::$_createFullData ? 100 : 10)) {
            $this->_createContact($this->_addresses[$this->_createdContactIndex+$i], $this->_images[($this->_createdContactIndex+$i)]);
            $i++;
        }
        $this->_createdContactIndex = $this->_createdContactIndex + $i;
    }

    /**
     * creates 100 contacts for rwright
     */
    protected function _createContactsForRwright()
    {
        $i=0;
        while ($i < (static::$_createFullData ? 100 : 10)) {
            $this->_createContact($this->_addresses[$this->_createdContactIndex+$i], $this->_images[($this->_createdContactIndex+$i)]);
            $i++;
        }
        $this->_createdContactIndex = $this->_createdContactIndex + $i;
    }

    /**
     * creates 100 contacts for sclever
     */
    protected function _createContactsForSclever()
    {
        $i=0;
        while ($i < (static::$_createFullData ? 100 : 10)) {
            $this->_createContact($this->_addresses[$this->_createdContactIndex+$i], $this->_images[($this->_createdContactIndex+$i)]);
            $i++;
        }
        $this->_createdContactIndex = $this->_createdContactIndex + $i;
    }
}
