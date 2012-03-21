<?php
/**
 * Addressbook csv generation class
 *
 * @package     Addressbook
 * @subpackage    Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * Addressbook csv generation class
 * 
 * @package     Addressbook
 * @subpackage    Export
 * 
 */
class Addressbook_Export_Csv extends Tinebase_Export_Csv
{
    /**
     * @var string application name of this export class
     */
    protected $_applicationName = 'Addressbook';
    
    /**
     * the record model
     *
     * @var string
     */
    protected $_modelName = 'Addressbook_Model_Contact';
    
    /**
     * fields to skip
     * 
     * @var array
     */
    protected $_skipFields = array(
        'id'                    ,
        'created_by'            ,
        'creation_time'         ,
        'last_modified_by'      ,
        'last_modified_time'    ,
        'is_deleted'            ,
        'deleted_time'          ,
        'deleted_by'            ,
        'jpegphoto'
    );
}
