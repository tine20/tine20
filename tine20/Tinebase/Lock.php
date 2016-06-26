<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Locking utility class
 *
 * @package     Tinebase
 */
class Tinebase_Lock
{
    /**
     * @param string $id
     * @return bool|null bool on success / failure, null if not supported
     */
    public static function aquireDBSessionLock($id)
    {
        $id = 'tine20_' . $id;
        $db = Tinebase_Core::getDb();

        if ($db instanceof Zend_Db_Adapter_Pdo_Mysql) {
            if (    ($stmt = $db->query('SELECT IS_FREE_LOCK("' . $id . '")')) &&
                    $stmt->setFetchMode(Zend_Db::FETCH_NUM) &&
                    ($row = $stmt->fetch()) &&
                    $row[0] == 1) {
                $stmt->closeCursor();
                if (    ($stmt = $db->query('SELECT GET_LOCK("' . $id . '", 1)')) &&
                        $stmt->setFetchMode(Zend_Db::FETCH_NUM) &&
                        ($row = $stmt->fetch()) &&
                        $row[0] == 1) {
                    return true;
                }
            }
            return false;

        } elseif($db instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            $id = sha1($id, true);
            $intId = unpack('N', $id);
            if (    ($stmt = $db->query('SELECT pg_try_advisory_lock(' . current($intId) . ')')) &&
                    $stmt->setFetchMode(Zend_Db::FETCH_NUM) &&
                    ($row = $stmt->fetch()) &&
                    $row[0] == 1) {
                return true;
            }
            return false;

        } else {
            // not implemented / supported
            return null;
        }
    }

    /**
     * @param string $id
     * @return bool|null bool on success / failure, null if not supported
     */
    public static function releaseDBSessionLock($id)
    {
        $id = 'tine20_' . $id;
        $db = Tinebase_Core::getDb();

        if ($db instanceof Zend_Db_Adapter_Pdo_Mysql) {
            if (    ($stmt = $db->query('SELECT RELEASE_LOCK("' . $id . '")')) &&
                    $stmt->setFetchMode(Zend_Db::FETCH_NUM) &&
                    ($row = $stmt->fetch()) &&
                    $row[0] == 1) {
                return true;
            }
            return false;

        } elseif($db instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            $id = sha1($id, true);
            $intId = unpack('N', $id);
            if (    ($stmt = $db->query('SELECT pg_advisory_unlock(' . current($intId) . ')')) &&
                $stmt->setFetchMode(Zend_Db::FETCH_NUM) &&
                ($row = $stmt->fetch()) &&
                $row[0] == 1) {
                return true;
            }
            return false;

        } else {
            // not implemented / supported
            return null;
        }
    }
}