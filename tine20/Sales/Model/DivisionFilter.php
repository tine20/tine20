<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Division filter Class
 * @package     Sales
 */
class Sales_Model_DivisionFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Sales';

    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Sales_Model_Division';

    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'                  => array('filter' => 'Tinebase_Model_Filter_Id'),
        'title'               => array('filter' => 'Tinebase_Model_Filter_Text'),
        'query'               => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('name'))),
    );
}
