<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 */

/**
 * Addressbook duplicate contact exception
 * 
 * @package     Addressbook
 * @subpackage  Exception
 */
class Addressbook_Exception_DuplicateContact extends Tinebase_Exception_Duplicate
{
    /**
     * model name
     * 
     * @var string
     */
    protected $_modelName = 'Addressbook_Model_Contact';
}
