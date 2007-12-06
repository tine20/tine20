<?php

/**
 * this classes provides access to the sql table egw_metacrm_productsource
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Projects.php 82 2007-09-12 16:35:14Z twadewitz $
 *
 */
class Crm_Backend_Sql_Productsource extends Zend_Db_Table_Abstract
{
    protected $_name = 'egw_metacrm_productsource';

    
    /**
     * fetch all entries matching where parameter.
     *
     * @param string/array $_where where filter
     * @param string $_order order by 
     * @param int $_count maximum rows to return
     * @param int $_offset how many rows to io
     * @param array $_readACL read ACL; return row only if owner is in in_array; can be NULL to disable ACL check
     * @return unknown
     */
    public function fetchAll($_where = NULL, $_order = NULL, $_dir = NULL, $_count = NULL, $_offset = NULL, $_readACL = NULL)
    {
		$result = parent::fetchAll();                                
        return $result;
    }
} 