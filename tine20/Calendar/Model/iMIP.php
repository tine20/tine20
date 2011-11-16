<?php
/**
 * @package     Calendar
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Model of an prepared iMIP Message
 *
 * @package     Calendar
 * @subpackage  Model
 */
class Calendar_Model_iMIP extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * NOTE: _Must_ be set by the derived classes!
     * 
     * @var string
     */
    protected $_identifier = 'id';
    
    /**
     * validators
     *
     * @var array
     */
    protected $_validators = array(
        'id'                   => array('allowEmpty' => true,         ), // not used
        'event'                => array('allowEmpty' => false         ),
        'method'               => array('allowEmpty' => false,        ),
        'userAgent'            => array('allowEmpty' => true,         ),
    );
}