<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Task priority Record Class
 * 
 * @package    Tasks
 * @subpackage Model
 */
class Tasks_Model_Priority extends Tinebase_Config_KeyFieldRecord
{
    /**
     * prio constant: LOW
     * 
     * @var string
     */
    const LOW         = 'LOW';

    /**
     * prio constant: NORMAL
     * 
     * @var string
     */
    const NORMAL      = 'NORMAL';
    
    /**
     * prio constant: HIGH
     * 
     * @var string
     */
    const HIGH        = 'HIGH';
    
    /**
     * prio constant: URGENT
     * 
     * @var string
     */
    const URGENT      = 'URGENT';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tasks';
    
    /**
     * return priority mapping (e.g. for ActiveSync)
     * 
     * @return array
     */
    public static function getMapping()
    {
        return array(
            0 => self::LOW,
            1 => self::NORMAL,
            2 => self::HIGH,
            3 => self::URGENT,
        );
    }
}
