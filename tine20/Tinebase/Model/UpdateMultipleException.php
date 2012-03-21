<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <alex@stintzing.net>
 *
 */

/**
 * class Tinebase_Model_UpdateMultipleException
 *
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Model_UpdateMultipleException extends Tinebase_Record_Abstract
{
    /**
     * identifier field name
     *
     * @var string
     */
    protected $_identifier = 'id';

    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';

    /**
     * record validators
     *
     * @var array
     */
    protected $_validators = array(
        'id'                => array('allowEmpty' => TRUE),
        'exception'         => array('allowEmpty' => TRUE),
        'code'              => array('allowEmpty' => TRUE),
        'message'           => array('allowEmpty' => TRUE),
        'record'            => array('allowEmpty' => TRUE),
    );
}
