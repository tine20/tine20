<?php
/**
 * Tine 2.0
 *
 * @package     MailFiler
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      AirMike <airmike23@gmail.com>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold data representing one node in the tree
 * 
 * @package     MailFiler
 * @subpackage  Model
 * @property    string             contenttype
 * @property    Tinebase_DateTime  creation_time
 * @property    string             hash
 * @property    string             name
 * @property    Tinebase_DateTime  last_modified_time
 * @property    string             object_id
 * @property    string             size
 * @property    string             type
 */
class MailFiler_Model_Node extends Tinebase_Model_Tree_Node
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'MailFiler';

    /**
     * overwrite constructor to add more filters
     *
     * @param mixed $_data
     * @param bool $_bypassFilters
     * @param mixed $_convertDates
     * @return void
     */
    public function __construct($_data = NULL, $_bypassFilters = FALSE, $_convertDates = TRUE)
    {
        $this->_validators['message'] = array(Zend_Filter_Input::ALLOW_EMPTY => true);
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
}
