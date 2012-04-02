<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 */

/**
 * record exception
 *
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_Record_SystemContainer extends Tinebase_Exception
{
    /**
     * title for the exception dialog
     * @var string
     */
    protected $_title;

    /**
    * the constructor
    *
    * @param string $_message
    * @param string $_title
    * @param int $_code (default: 600 System Container)
    */
    public function __construct($_message, $_title, $_code = 600)
    {
        $this->_title = $_title;
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
     * returns existing nodes info as array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
                'code'          => $this->getCode(),
                'message'       => $this->getMessage(),
                'title'         => $this->getTitle()
                );
    }
}
