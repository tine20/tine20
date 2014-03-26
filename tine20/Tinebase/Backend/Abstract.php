<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 */

/**
 * Abstract class for a Tine 2.0 backend
 * 
 * @package     Tinebase
 * @subpackage  Backend
 */
abstract class Tinebase_Backend_Abstract extends Tinebase_Pluggable_Abstract implements Tinebase_Backend_Interface
{
    /**
     * backend type constant
     *
     * @var string
     */
    protected $_type = NULL;
        
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = NULL;
        
    /**
     * get backend type
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }
    
    /**
     * get model name
     *
     * @return string
     */
    public function getModelName()
    {
        return $this->_modelName;
    }
}
