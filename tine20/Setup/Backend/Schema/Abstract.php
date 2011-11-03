<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jonas Fischer <j.fischer@metaways.de>
 */


abstract class Setup_Backend_Schema_Abstract
{
 
    /**
     * Instance of Setup_Backend_Abstract with public getter {@see getBackend()}
     * @var Setup_Backend_Abstract
     */
    protected $_backend;
    
     /**
     * the name of the table
     *
     * @var string
     */
    public $name;

    /**
     * constructor of this class
     *
     * @param string|SimpleXMLElement $_declaration the xml definition of the field
     */
    public function __construct($_declaration = NULL)
    {
        $this->isValid(); //check validity (and thus implicitly log warnings)
    }
    
    /**
     * Setter for {@see $name} property
     * 
     * @param string $_name
     * @return void
     */      
    public function setName($_name)
    {
        $this->name = (string)$_name;
    }
    
    /**
     * Validate "syntax" of this field
     * 
     * @throws Setup_Exception_InvalidSchema if {@param $throwException} is set to true
     *  
     * @param $throwException
     * @return bool
     */
    public function isValid($throwException = false)
    {
        $isValid = true;
        $messages = array();
        
        $nameValidator = new Zend_Validate_StringLength(1, 23);
        if (!$nameValidator->isValid($this->name)) {
            $isValid = false;
            $messages = array_merge($messages, $nameValidator->getErrors());
        }

        if (!$isValid) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Invalid schema specified for field ' . $this->name . ': ' . print_r($messages, 1));
            if ($throwException) {
                throw new Setup_Exception_InvalidSchema('Invalid schema specified for field ' . $this->name . ': ' . print_r($messages, 1));
            }
        }           

        return $isValid;
    }
    
    /**
     * Getter for {@see $_backend} property
     * 
     * Lazy loading: Initializes $_backend on first request
     * 
     * @return Setup_Backend_Abstract
     */
    public function getBackend()
    {
        if (!isset($this->_backend)) {
            $this->_backend = Setup_Backend_Factory::factory();
        }
        return $this->_backend;
    }
}
