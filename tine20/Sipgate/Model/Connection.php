<?php
/**
 * Tine 2.0
 *
 * @package     Sipgate
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold contact data
 *
 * @package     Sipgate
 */
class Sipgate_Model_Connection extends Tinebase_Record_Abstract
{
    /**
     * const to describe contact of current account id independent
     *
     * @var string
     */
    const CURRENTCONTACT = 'currentContact';

    /**
     * contact type: contact
     *
     * @var string
     */
    const CONTACTTYPE_CONTACT = 'contact';

    /**
     * contact type: user
     *
     * @var string
     */
    const CONTACTTYPE_USER = 'user';

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
    protected $_application = 'Sipgate';

    /**
     * list of zend inputfilter
     *
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(

    );

    /**
     * list of zend validator
     *
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'            => array('allowEmpty' => true),
        'entry_id'      => array('allowEmpty' => true),
        'status'        => array('allowEmpty' => true),
        'tos'           => array('allowEmpty' => true),
        'local_uri'     => array('allowEmpty' => true),
        'remote_uri'    => array('allowEmpty' => true),
        'line_id'       => array('allowEmpty' => true),
        'timestamp'     => array('allowEmpty' => true),
        'creation_time' => array('allowEmpty' => true),
        'tarif'         => array('allowEmpty' => true),
        'duration'      => array('allowEmpty' => true),
        'units_charged' => array('allowEmpty' => true),
        'price_unit'    => array('allowEmpty' => true),
        'price_total'   => array('allowEmpty' => true),
        'ticks_a'       => array('allowEmpty' => true),
        'ticks_b'       => array('allowEmpty' => true),
        'contact_id'    => array('allowEmpty' => true),
        'contact_name'    => array('allowEmpty' => true),
        'local_number'  => array('allowEmpty' => true),
        'remote_number' => array('allowEmpty' => true),
    );

    /**
     * name of fields containing datetime or or an array of datetime information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'timestamp',
        'creation_time',
    );


}
