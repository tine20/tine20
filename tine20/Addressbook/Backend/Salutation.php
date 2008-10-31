<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Asterisk.php 4159 2008-09-02 14:15:05Z p.schuele@metaways.de $
 *
 */

/**
 * salutation backend for the Addressbook application
 * 
 * @package     Addressbook
 * 
 */
class Addressbook_Backend_Salutation extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     */
    public function __construct ()
    {
        parent::__construct(SQL_TABLE_PREFIX . 'addressbook_salutations', 'Addressbook_Model_Salutation');
    }    
}
