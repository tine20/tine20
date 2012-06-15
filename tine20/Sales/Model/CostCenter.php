<?php
/**
 * class to hold CostCenter data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        add CostCenter status table
 */

/**
 * class to hold CostCenter data
 *
 * @package     Sales
 */
class Sales_Model_CostCenter extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the filed which
     * represents the identifier
     *
     * @var string
     */
    protected $_identifier = 'id';

    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Sales';

    /**
     * list of zend validator
     *
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'number'                => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'remark'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        // relations (linked users/groups and customers
        'relations'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
    );
}
