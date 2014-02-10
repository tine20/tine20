<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  PersistentFilter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * class Tinebase_Model_PersistentFilterGrant
 * 
 * @package     Tinebase
 * @subpackage  PersistentFilter
 */
class Tinebase_Model_PersistentFilterGrant extends Tinebase_Model_Grants 
{
    /**
     * get all possible grants
     *
     * @return  array   all container grants
     */
    public static function getAllGrants()
    {
        $allGrants = array(
            self::GRANT_READ,
            self::GRANT_EDIT,
            self::GRANT_DELETE,
        );
    
        return $allGrants;
    }
}
