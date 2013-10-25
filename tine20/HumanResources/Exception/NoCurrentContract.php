<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
    /**
     * the nearest contract, if any
     * 
     * @var unknown
     */
    protected $_nearest_record = NULL;

    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'No current contract!'; // _('No current contract!')
    
    /**
     * @see SPL Exception
     */
    protected $message = "A current contract could not be found!"; // _("A current contract could not be found!")
    
    /**
     * @see SPL Exception
     */
    protected $code = 910;
    
    /**
     * add record to exception
     * @param Tinebase_Record_Interface $_record
     */
    public function addRecord(Tinebase_Record_Interface $_record)
    {
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
