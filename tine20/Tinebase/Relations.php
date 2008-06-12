<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Relations
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Class for handling relations between application records.
 * 
 * @package     Tinebase
 * @subpackage  Relations 
 */
class Tinebase_Relations
{
    /**
     * set all relations of a given record
     * 
     * NOTE: given relation data is expected to be an array atm.
     * 
     * @param  string $_model        own model to get relations for
     * @param  string $_backend      own backend to get relations for
     * @param  string $_id           own id to get relations for 
     * @param  array  $_relationData data for relations to create
     * @param  bool   $_ignoreAcl    create relations without checking permissions
     * @return void
     */
    public function setRelations($_model, $_backend, $_id, $_relationData, $_ignoreAcl=false)
    {
        // check for toCreate / toDelete
        
    }
    /**
     * get all relations of a given record
     * 
     * @param  string $_model     own model to get relations for
     * @param  string $_backend   own backend to get relations for
     * @param  string $_id        own id to get relations for 
     * @param  bool   $_ignoreAcl get relations without checking permissions
     * @return Tinebase_Record_RecordSet of Tinebase_Relation_Model_Relation
     */
    public function getRelations($_model, $_backend, $_id, $_ignoreAcl=false)
    {
        
    }
}