<?php
/**
 * Tine 2.0
 * 
 * @package     SimpleFAQ
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Patrick Ryser <patrick.ryser@gmail.com>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * backend for FAQ
 *
 * @package     SimpleFAQ
 * @subpackage  Backend
 */
class SimpleFAQ_Backend_Faq extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'simple_faq';

    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'SimpleFAQ_Model_Faq';

    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;

}
