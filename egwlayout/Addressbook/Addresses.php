<?php

class Addressbook_Addresses extends Zend_Db_Table_Abstract
{
    protected $_name = 'egw_addressbook';
    #protected $_primary = 'contact_id';
    protected $_owner = 'contact_owner';
    
    protected static $filters = array(
        '*'                     => 'StringTrim',
        'contact_email'         => array('StringTrim', 'StringToLower'),
        'contact_email_home'    => array('StringTrim', 'StringToLower'),
        'contact_url'           => array('StringTrim', 'StringToLower'),
        'contact_url_home'      => array('StringTrim', 'StringToLower'),
    );
    
    protected static $validators = array(
        'adr_one_countryname'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_locality'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_postalcode'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_region'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_street'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_street2'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_countryname'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_locality'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_postalcode'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_region'	=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_street'	=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_street2'	=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_assistent'	=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_bday'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_email'		=> array('EmailAddress', Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_email_home'	=> array('EmailAddress', Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_note'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_role'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_room'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_title'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_url'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_url_home'	=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_family'		=> array(),
        'n_given'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_middle'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_prefix'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_suffix'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'org_name'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'org_unit'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_assistent'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_car'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_cell'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_cell_private'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_fax'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_fax_home'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_home'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_pager'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_work'              => array(Zend_Filter_Input::ALLOW_EMPTY => true)
    );
    
    public function delete(array $_key)
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $where  = $this->getAdapter()->quoteInto($this->_primary[1] . ' IN (?)', $_key);
        $where .= $this->getAdapter()->quoteInto(' AND ' . $this->_owner . ' = ?', $currentAccount->account_id);
        
        error_log($where);
        
        parent::delete($where);
    }
    
    public function getTotalCount()
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        return $this->getAdapter()->fetchOne('SELECT count(*) FROM '. $this->_name . ' WHERE ' . $this->_owner . ' = ' . $currentAccount->account_id);
    }
    
    static public function getFilter()
    {
        return self::$filters;
    }

    static public function getValidator()
    {
        return self::$validators;
    }
    
    public function fetchAll($_where = null, $_order = null, $_count = null, $_offset = null)
    {
        if(isset($this->_owner)) {
            $currentAccount = Zend_Registry::get('currentAccount');
            
            if($_where !== NULL) {
                $where = $_where . ' AND ';
            }
            $where .=  $this->getAdapter()->quoteInto($this->_owner . ' = ?', $currentAccount->account_id);
        } else {
            $where = $_where;
        }
        
        return parent::fetchAll($where, $_order, $_count, $_offset);
    }
    
    public function find($_key)
    {
        $where = $this->getAdapter()->quoteInto($this->_primary[1] . ' = ?', $_key);
        return parent::fetchAll($where);
    }
    
    public function insert($_data)
    {
        if(isset($this->_owner)) {
            $currentAccount = Zend_Registry::get('currentAccount');
            $_data[$this->_owner] = $currentAccount->account_id;
        }
    
        return parent::insert($_data);
    }
    
    public function update(array $_data, $_where)
    {
        if(isset($this->_owner)) {
            $currentAccount = Zend_Registry::get('currentAccount');
            $where = $_where . ' AND ' . $this->getAdapter()->quoteInto($this->_owner . ' = ?', $currentAccount->account_id);
        } else {
            $where = $_where;
        }
        
        return parent::update($_data, $where);
    }
}

?>
        
        