<?php

/**
 * this classes provides access to the sql table egw_accounts
 * 
 * @package     Egwbase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Egwbase_Acl_Sql_Rights extends Zend_Db_Table_Abstract
{
    protected $_name = 'egw_acl';
}
        