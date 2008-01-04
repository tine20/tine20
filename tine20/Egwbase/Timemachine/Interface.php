<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Egwbase
 * @subpackage  Timemachine 
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */


/**
 * Timemachine works on record basis and supplies records as they where at a
 * given point in history. Moreover it answers the question which records have
 * been added, modiefied or deleted in a given timespan.
 * 
 * This are the most important usecases timemachine is designed for:
 * - Provide a consistent data view for a given time. This is important for 
 *   syncronisation engines like syncML.
 * - Provide history information, which are needed to implement a sophisticated
 *   concurrency management on field basis.
 * - Provide datas for record history investigations.
 * - Provide datas for desaster recovery.
 * 
 * Egwbase_Timemachine interfaces/classes build a framework, which needs to be 
 * implemented/extended by the backends of an application.
 * 
 * As Timemachine could be invoked for sync, but also for concurrency issues, it
 * has to deal with UIDs (string) on the one hand but also with ids (int) on the
 * other hand. If an app does not deal with UIDs (e.g. its not intended to 
 * paticipate sync), it has to throw exceptions when a UID handling method gets 
 * invoked.
 * 
 * NOTE: Timespans are allways defined, with the beginning point included and
 * the end point excluded. Mathematical: [_from, _until)
 * NOTE: Records _at_ a given point in history include changes which contingently
 * where made _at_ the end of time resolution of this point
 * 
 * @package Egwbase
 * @subpackage Timemachine
 */
interface Egwbase_Timemachine_Interface
{
    
    /**
     * Returns ids(int)/uids(strings) of records which where created in a given timespan.
     * 
     * @param Zend_Date _from beginning point of timespan, including point itself
     * @param Zend_Date _until end point of timespan, excluding point itself
     * @param Egwbase_Record_Filter _filter
     * @param bool _returnUIDs wether to use global (string) or local (int) identifiers
     * @return array array of identifiers
     * @access public
     */
    public function getCreated( Zend_Date $_from, Zend_Date $_until, Egwbase_Record_Filter $_filter, $_returnUIDs = FALSE );
    
    /**
     * Returns ids(int)/uids(strings) of records which where modified in a given timespan.
     * 
     * @param Zend_Date _from beginning point of timespan, including point itself
     * @param Zend_Date _until end point of timespan, excluding point itself
     * @param Egwbase_Record_Filter _filter
     * @param bool _returnUIDs wether to use global (string) or local (int) identifiers
     * @return array array of identifiers
     * @access public
     */
    public function getModified( Zend_Date $_from, Zend_Date $_until, Egwbase_Record_Filter $_filter, $_returnUIDs = FALSE );
    
    /**
     * Returns ids(int)/uids(strings) of records which where deleted in a given timespan.
     * 
     * @param Zend_Date _from beginning point of timespan, including point itself
     * @param Zend_Date _until end point of timespan, excluding point itself
     * @param Egwbase_Record_Filter _filter
     * @param bool _returnUIDs wether to use global (string) or local (int) identifiers
     * @return array array of identifiers
     * @access public
     */
    public function getDeleted( Zend_Date $_from, Zend_Date $_until, Egwbase_Record_Filter $_filter, $_returnUIDs = FALSE );
    
    /**
     * Returns a record as it was at a given point in history
     * 
     * @param [string|int] _id 
     * @param Zend_Date _at 
     * @param bool _idIsUID wether global (string) or local (int) identifiers are given as _id
     * @return Egwbase_Record
     * @access public
     */
    public function getRecord( $_id,  Zend_Date $_at, $_idIsUID = FALSE);
    
    /**
     * Returns a set of records as they where at a given point in history
     * 
     * @param array _ids array of [string|int] 
     * @param Zend_Date _at 
     * @param bool _idsAreUIDs wether global (string) or local (int) identifiers are given as _ids
     * @return Egwbase_Record_RecordSet
     * @access public
     */
    public function getRecords( array $_ids,  Zend_Date $_at, $_idsAreUIDs = FALSE );

} // end of Egwbase_Timemachine_Interface
?>
