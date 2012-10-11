<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 *
 */

/**
 * generic system exception which does not trigger an error reporting assistent
 * 
 * used to signal installation problems like:
 * - server connection problems
 * - missconfiguration
 * - ...
 * 
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_SystemGeneric extends Tinebase_Exception
{
    /**
     * @var string _('Generic System Exception')
     */
    protected $_title = 'Generic System Exception';
    
    /**
     * @var string
     */
    protected $_appName = 'Tinebase';
    
    public function __construct($_message, $_code=600)
    {
        parent::__construct($_message, $_code);
    }
    
    /**
     * get the title
     * @return string
     */
    public function getTitle() {
        return $this->_title;
    }
    
    /**
     * set application name
     * used to get the translation object
     * 
     * @param string $_appName
     */
    public function setAppName($_appName)
    {
        $this->_appName = $_appName;
    }
    
    /**
     * set custom title
     * 
     * @param string $_title
     */
    public function setTitle($_title)
    {
        $this->_title = $_title;
    }
    
    /**
     * returns existing nodes info as array
     *
     * @return array
     */
    public function toArray()
    {
        try {
            $translation = Tinebase_Translation::getTranslation($this->_appName);
            
            return array(
                'code'          => $this->getCode(),
                'message'       => $translation->_($this->getMessage()),
                'title'         => $translation->_($this->getTitle()),
            );
        } catch (Exception $e) {
            return array(
                'code'          => $this->getCode(),
                'message'       => $this->getMessage(),
                'title'         => $this->getTitle(),
            );
        }
    }
    
}