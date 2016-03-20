<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */
class Addressbook_Model_ListMemberRole extends Tinebase_Record_Abstract
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Addressbook';

    /**
     * key in $_validators/$_properties array for the field which
     * represents the identifier
     *
     * @var string
     */
    protected $_identifier = 'id';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Record/Abstract::$_validators
     */
    protected $_validators = array(
        // tine record fields
        'id'                   => array('allowEmpty' => true,         ),

        // record specific
        'list_id'              => array('allowEmpty' => false         ),
        'list_role_id'         => array('allowEmpty' => false         ),
        'contact_id'           => array('allowEmpty' => false         ),
    );
}
