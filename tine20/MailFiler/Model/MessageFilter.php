<?php
/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        replace 'custom' filters with normal filter classes
 * @todo        should implement acl filter
 */

/**
 * cache entry filter Class
 * 
 * @package     Felamimail
 */
class MailFiler_Model_MessageFilter extends Felamimail_Model_MessageFilter 
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'MailFiler';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = MailFiler_Model_Message::class;
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'            => array('filter' => 'Tinebase_Model_Filter_Id', 'options' => array('modelName' => 'MailFiler_Model_Message')),
        'node_id'       => array('filter' => 'Tinebase_Model_Filter_Id', 'options' => array('modelName' => 'MailFiler_Model_Node')),
        'query'         => array(
            'filter'        => 'Tinebase_Model_Filter_Query', 
            'options'       => array('fields' => array('subject', 'from_email', 'from_name'))
        ),
        'subject'       => array('filter' => 'Tinebase_Model_Filter_Text'),
        'from_email'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'from_name'     => array('filter' => 'Tinebase_Model_Filter_Text'),
        'received'      => array('filter' => 'Tinebase_Model_Filter_Date'),
        'messageuid'    => array('filter' => 'Tinebase_Model_Filter_Int'),
    // custom filters
        'to'            => array('custom' => true, 'requiredCols' => array('to' => 'mailfiler_message_to.*')),
        'cc'            => array('custom' => true, 'requiredCols' => array('cc' => 'mailfiler_message_cc.*')),
        'bcc'           => array('custom' => true, 'requiredCols' => array('bcc' => 'mailfiler_message_bcc.*')),
        'flags'         => array('custom' => true, 'requiredCols' => array('flags' => 'mailfiler_msg_flag.flag')),
    );
}
