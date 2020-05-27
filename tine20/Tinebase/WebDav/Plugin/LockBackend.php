<?php
/**
 * WebDav Lock PDO Backend
 *
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Sabre\DAV\Locks\LockInfo;
use Sabre\DAV\Locks\Backend\AbstractBackend;

/**
 * The Lock manager allows you to handle all file-locks centrally.
 *
 * This Lock Manager stores all its data in a database. You must pass a PDO
 * connection object in the constructor.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */

/**
 * WebDav Lock PDO Backend
 *
 * @package     Tinebase
 * @subpackage  WebDav
 */
class Tinebase_WebDav_Plugin_LockBackend extends AbstractBackend
{
    protected $tableName;
    protected $pdo;

    public function __construct()
    {
        $this->tableName = SQL_TABLE_PREFIX . Tinebase_Model_WebDavLock::TABLE_NAME;
        $this->pdo = Tinebase_Core::getDb()->getConnection();
    }

    /**
     * Returns a list of Sabre\DAV\Locks\LockInfo objects
     *
     * This method should return all the locks for a particular uri, including
     * locks that might be set on a parent uri.
     *
     * If returnChildLocks is set to true, this method should also look for
     * any locks in the subtree of the uri for locks.
     *
     * @param string $uri
     * @param bool $returnChildLocks
     * @return array
     */
    public function getLocks($uri, $returnChildLocks)
    {
        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->tableName . ' WHERE timeout < ?');
        $stmt->execute([time()]);

        // NOTE: the following 10 lines or so could be easily replaced by
        // pure sql. MySQL's non-standard string concatenation prevents us
        // from doing this though.
        $query = 'SELECT owner, token, timeout, created, scope, depth, uri FROM '.$this->tableName.' WHERE ((uri = ?)';
        $params = array($uri);

        // We need to check locks for every part in the uri.
        $uriParts = explode('/',$uri);

        // We already covered the last part of the uri
        array_pop($uriParts);

        $currentPath='';

        foreach($uriParts as $part) {

            if ($currentPath) $currentPath.='/';
            $currentPath.=$part;

            $query.=' OR (depth!=0 AND uri = ?)';
            $params[] = $currentPath;

        }

        if ($returnChildLocks) {

            $query.=' OR (uri LIKE ?)';
            $params[] = $uri . '/%';

        }
        $query.=')';

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetchAll();

        $lockList = array();
        foreach($result as $row) {

            $lockInfo = new LockInfo();
            $lockInfo->owner = $row['owner'];
            $lockInfo->token = $row['token'];
            $lockInfo->timeout = $row['timeout'] - $row['created'];
            $lockInfo->created = $row['created'];
            $lockInfo->scope = $row['scope'];
            $lockInfo->depth = $row['depth'];
            $lockInfo->uri   = $row['uri'];
            $lockList[] = $lockInfo;

        }

        return $lockList;

    }

    /**
     * Locks a uri
     *
     * @param string $uri
     * @param LockInfo $lockInfo
     * @return bool
     */
    public function lock($uri, LockInfo $lockInfo) {

        // We're making the lock timeout 30 minutes
        $timeout = 30 * 60;
        $lockInfo->created = time();
        $lockInfo->timeout = $lockInfo->created + $timeout;
        $lockInfo->uri = $uri;

        $locks = $this->getLocks($uri,false);
        $exists = false;
        foreach($locks as $lock) {
            if ($lock->token == $lockInfo->token) $exists = true;
        }

        if ($exists) {
            $stmt = $this->pdo->prepare('UPDATE '.$this->tableName.' SET owner = ?, timeout = ?, scope = ?, depth = ?, uri = ?, created = ? WHERE token = ?');
            $stmt->execute(array($lockInfo->owner,$lockInfo->timeout,$lockInfo->scope,$lockInfo->depth,$uri,$lockInfo->created,$lockInfo->token));
        } else {
            $stmt = $this->pdo->prepare('INSERT INTO '.$this->tableName.' (owner,timeout,scope,depth,uri,created,token) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute(array($lockInfo->owner,$lockInfo->timeout,$lockInfo->scope,$lockInfo->depth,$uri,$lockInfo->created,$lockInfo->token));
        }
        $lockInfo->timeout = $timeout;

        return true;

    }



    /**
     * Removes a lock from a uri
     *
     * @param string $uri
     * @param LockInfo $lockInfo
     * @return bool
     */
    public function unlock($uri, LockInfo $lockInfo) {

        $stmt = $this->pdo->prepare('DELETE FROM '.$this->tableName.' WHERE uri = ? AND token = ?');
        $stmt->execute(array($uri,$lockInfo->token));

        return $stmt->rowCount()===1;

    }

}

