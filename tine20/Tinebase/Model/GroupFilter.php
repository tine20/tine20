<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 *  Groups filter class
 * 
 * @package     Tinebase
 * @subpackage  Groups 
 */
class Tinebase_Model_GroupFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Tinebase_Model_Group';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'name'           => array('filter' => 'Tinebase_Model_Filter_Text'),
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('name', 'description'))),
    );
}
