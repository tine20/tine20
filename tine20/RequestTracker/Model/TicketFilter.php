<?php
/**
 * Tine 2.0
 * 
 * @package     RequestTracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * ticket filter
 *
 * @package     RequestTracker
 */
class RequestTracker_Model_TicketFilter extends Tinebase_Model_Filter_FilterGroup 
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'RequestTracker';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'RequestTracker_Model_Ticket';
    
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'RequestTracker_Model_TicketFilter';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'             => array('filter' => 'Tinebase_Model_Filter_Int'),
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('id', 'subject'))), // NOTE: RT cant deal with mixed and/or filters
        'queue'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'status'         => array('filter' => 'Tinebase_Model_Filter_Int'),
        'subject'        => array('filter' => 'Tinebase_Model_Filter_Text'),
        'requestor'      => array('filter' => 'Tinebase_Model_Filter_Text'),
        'owner'          => array('filter' => 'Tinebase_Model_Filter_Text'),
    );
    
}
