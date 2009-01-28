<?php

class Tinebase_Model_Filter_DbGroupSelect
{
    /**
     * @var Zend_Db_Select
     */
    protected $_select = NULL;
    
    public function __construct(Zend_Db_Select $_select)
    {
        $this->_select = $_select;
    }
    
    public function __call($_name, $_arguments)
    {
        call_user_func_array(array($this->_select, $_name), $_arguments);
    }
    
    public function assemble()
    {
        
    }
}