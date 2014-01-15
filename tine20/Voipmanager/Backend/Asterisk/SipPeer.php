<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Asterisk peer sql backend
 *
 * @package     Voipmanager
 * @subpackage  Backend
 */
class Voipmanager_Backend_Asterisk_SipPeer extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'asterisk_sip_peers';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Voipmanager_Model_Asterisk_SipPeer';
    
    /**
     * foreign tables (key => tablename)
     *
     * @var array
     */
    protected $_foreignTables = array(
        'context'  => array(
            'table'         => 'asterisk_context',
            'joinOn'        => 'id',
            'joinId'        => 'context_id',
            'select'        => array('context' => 'name'),
            'singleValue'   => true,
        ),
    );
    
    /**
     * converts record into raw data for adapter
     *
     * @param  Tinebase_Record_Abstract $_record
     * @return array
     */
    protected function _recordToRawData($_record)
    {
        // special handling, convert to UNIX timestamp
        if(isset($_record['regseconds']) && $_record['regseconds'] instanceof DateTime) {
            $_record['regseconds'] = $_record['regseconds']->getTimestamp();
        }
        $result = parent::_recordToRawData($_record);
        
        // context is joined from the asterisk_context table and can not be set here
        unset($result['context']);
        
        return $result;
    }
}
