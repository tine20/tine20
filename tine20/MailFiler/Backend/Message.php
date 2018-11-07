<?php
/**
 * Tine 2.0
 *
 * @package     MailFiler
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * sql cache backend class for MailFiler messages
 *
 * @package     MailFiler
 */
class MailFiler_Backend_Message extends Felamimail_Backend_Cache_Sql_Message
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'mailfiler_message';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'MailFiler_Model_Message';
    
    /**
     * foreign tables (key => tablename)
     *
     * @var array
     */
    protected $_foreignTables = array(
        'to'    => array(
            'table'     => 'mailfiler_message_to',
            'joinOn'    => 'message_id',
            'field'     => 'email',
        ),
        'cc'    => array(
            'table'  => 'mailfiler_message_cc',
            'joinOn' => 'message_id',
            'field'  => 'email',
        ),
        'bcc'    => array(
            'table'  => 'mailfiler_message_bcc',
            'joinOn' => 'message_id',
            'field'  => 'email',
        ),
        'flags'    => array(
            'table'         => 'mailfiler_msg_flag',
            'joinOn'        => 'message_id',
            'field'         => 'flag',
        ),
    );
}
