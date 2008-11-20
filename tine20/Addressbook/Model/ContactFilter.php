<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Addressbook Filter Class
 * @package Addressbook
 */
class Addressbook_Model_ContactFilter extends Tinebase_Record_AbstractFilter
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Addressbook';

    /**
     * the constructor
     * it is needed because we have more validation fields in Tasks
     * 
     * @param mixed $_data
     * @param bool $bypassFilters sets {@see this->bypassFilters}
     * @param bool $convertDates sets {@see $this->convertDates}
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        // add more filters
        $this->_validators = array_merge($this->_validators, array(
            'n_given'              => array('allowEmpty' => true           ),
            'n_family'             => array('allowEmpty' => true           ),
            'org_name'             => array('allowEmpty' => true           ),
            'title'                => array('allowEmpty' => true           ),
            'adr_one_street'       => array('allowEmpty' => true           ),
            'adr_one_postalcode'   => array('allowEmpty' => true           ),
            'adr_one_locality'     => array('allowEmpty' => true           ),
            'adr_two_street'       => array('allowEmpty' => true           ),
            'adr_two_postalcode'   => array('allowEmpty' => true           ),
            'adr_two_locality'     => array('allowEmpty' => true           ),
            'role'                 => array('allowEmpty' => true           ),
            'tag'                  => array('allowEmpty' => true           ),
        ));
        
        // define query fields
        $this->_queryFields = array(
            'n_family',
            'n_given',
            'org_name',
            'email',
            'adr_one_locality',
        );
        
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }

}
