<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        add user account acl filter
 */

/**
 *  persistent filter filter class
 * 
 * @package     Tinebase
 * @subpackage  Filter 
 */
class Tinebase_Model_PersistentFilterFilter extends Tinebase_Model_Filter_FilterGroup
{    
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('name'))),
        'application_id' => array('filter' => 'Tinebase_Model_Filter_ForeignId'),
        'account_id'     => array('filter' => 'Tinebase_Model_Filter_ForeignId'),
        'model'          => array('filter' => 'Tinebase_Model_Filter_Text'),
    );
}
