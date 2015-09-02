<?php

/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Mario Cesar Kolling <mario.kolling@serpro.gov.br>
 * @copyright   Copyright (c) 2009-2013 Serpro (http://www.serpro.gov.br)
 *
 * @todo It should be on Tinebase/Record
 */

class Expressomail_Record_SearchTotalCountRecordSet extends Tinebase_Record_RecordSet implements Expressomail_Record_SearchTotalCountInterface
{
    
    protected $searchTotalCount = 0;
    
    public function setSearchTotalCount($_totalCount) {
        $this->searchTotalCount = $_totalCount;
    }
    
    public function getSearchTotalCount() {
        return $this->searchTotalCount;
    }
}