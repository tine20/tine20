<?php
/**
 * holds information about the requested data
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Tinebase_Record_Expander_DataRequest_VirtualRelation extends Tinebase_Record_Expander_DataRequest
{
    public function __construct($_callback)
    {
        parent::__construct(Tinebase_Record_Expander_Abstract::DATA_FETCH_PRIO_AFTER_RELATION, $this, [], $_callback);
    }

    public function merge(Tinebase_Record_Expander_DataRequest $_dataRequest)
    {
    }

    public function getData()
    {
    }
}
