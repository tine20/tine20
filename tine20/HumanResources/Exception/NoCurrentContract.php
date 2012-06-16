<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 */

/**
 * No Current Contract Exception
 *
 * @package     HumanResources
 * @subpackage  Exception
 */
class HumanResources_Exception_NoCurrentContract extends HumanResources_Exception
{
    protected $_nearest_record = NULL;

    /**
     * construct
     *
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = 'A current contract could not be found!', $_code = 910) {
        parent::__construct($_message, $_code);
    }
    /**
     * add record to exception
     * @param Tinebase_Record_Interface $_record
     */
    public function addRecord(Tinebase_Record_Interface $_record)
    {
        $_record->workingtime_id = HumanResources_Controller_WorkingTime::getInstance()->get($_record->workingtime_id);
        $this->_nearest_record = $_record;
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
            'nearestRecord'  => $this->_nearest_record->toArray()
        );
    }
}
