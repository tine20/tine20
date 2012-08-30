<?php
/**
 * class to hold Sieve Vacation data
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold Vacation data
 * 
 * @property    array  addresses
 * @property    string  subject
 * @property    string  from
 * @property    string  mime
 * @property    string  reason
 * @property    integer  days
 * @property    boolean  enabled
 * @package     Felamimail
 */
class Felamimail_Model_Sieve_Vacation extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the field which 
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
    protected $_application = 'Felamimail';

    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'account_id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'addresses'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => array()),
        'subject'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'from'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'days'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 7),
        'enabled'               => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'date_enabled'          => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'mime'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'reason'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // not persistent, only used for message template
        'start_date'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'end_date'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_ids'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'template_id'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'signature'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );
    
    /**
    * name of fields containing datetime or an array of datetime
    * information
    *
    * @var array list of datetime fields
    */
    protected $_datetimeFields = array(
        'start_date',
        'end_date',
    );
    
    /**
     * set from sieve vacation object
     * 
     * @param Felamimail_Sieve_Vacation $fsv
     */
    public function setFromFSV(Felamimail_Sieve_Vacation $fsv)
    {
        $this->setFromArray($fsv->toArray());
    }
    
    /**
     * get sieve vacation object
     * 
     * @return Felamimail_Sieve_Vacation
     */
    public function getFSV()
    {
        $fsv = new Felamimail_Sieve_Vacation();
        
        $fsv->setEnabled($this->enabled)
            ->setDays((is_int($this->days) && $this->days > 0) ? $this->days : 7)
            ->setSubject($this->subject)
            ->setFrom($this->from)
            ->setMime($this->mime)
            ->setReason($this->reason)
            ->setDateEnabled($this->date_enabled);
        
        $this->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        if ($this->start_date instanceof Tinebase_DateTime) {
            $fsv->setStartdate($this->start_date->format('Y-m-d'));
        }
        if ($this->end_date instanceof Tinebase_DateTime) {
            $fsv->setEnddate($this->end_date->format('Y-m-d'));
        }
        $this->setTimezone('UTC');
        
        if (is_array($this->addresses)) {
            foreach ($this->addresses as $address) {
                $fsv->addAddress($address);
            }
        }
        
        return $fsv;
    }
}
