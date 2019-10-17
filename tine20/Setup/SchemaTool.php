<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use \Doctrine\ORM\Tools\SchemaTool;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\Common\Persistence\Mapping\StaticReflectionService;
use \Doctrine\Common\Persistence\Mapping\Driver\StaticPHPDriver;

/**
 * helper around docrine2/dbal schema tools
 */
class Setup_SchemaTool
{
    protected static $_dbParams = null;

    public static function setDBParams(array $dbParams)
    {
        static::$_dbParams = $dbParams;
    }

    /**
     * convert tine20config to dbal config
     *
     * @return array
     */
    public static function getDBParams()
    {
        if (null === static::$_dbParams) {
            $dbParams = Tinebase_Config::getInstance()->get('database')->toArray();
            $dbParams['driver'] = $dbParams['adapter'];
            $dbParams['user'] = $dbParams['username'];
            $db = Setup_Core::getDb();
            if ($db instanceof Zend_Db_Adapter_Pdo_Mysql) {
                if ($db->getConfig()['charset'] !== 'utf8' &&
                        Tinebase_Backend_Sql_Adapter_Pdo_Mysql::supportsUTF8MB4($db)) {
                    $dbParams['defaultTableOptions'] = [
                        'charset' => 'utf8mb4',
                        'collate' => 'utf8mb4_unicode_ci'
                    ];
                } else {
                    $dbParams['defaultTableOptions'] = [
                        'charset' => 'utf8',
                        'collate' => 'utf8_unicode_ci'
                    ];
                }
            }


            static::$_dbParams = $dbParams;
        }

        return static::$_dbParams;
    }

    /**
     * get orm config
     *
     * @return \Doctrine\ORM\Configuration
     * @throws Setup_Exception
     */
    public static function getConfig()
    {
        $mappingDriver = new Tinebase_Record_DoctrineMappingDriver();
        $tableNames = [];

        $config = self::getBasicConfig();
        $config->setMetadataDriverImpl($mappingDriver);

        try {
            /** @var Tinebase_Record_Interface $modelName */
            foreach ($mappingDriver->getAllClassNames() as $modelName) {
                if (null !== ($modelConfig = $modelName::getConfiguration()) &&
                    null !== ($tblName = $modelConfig->getTableName())) {
                    $tableNames[] = SQL_TABLE_PREFIX . $tblName;
                }
            }

            $config->setFilterSchemaAssetsExpression('/' . implode('|', $tableNames) . '/');
        } catch (Zend_Db_Exception $zde) {
            $config->setFilterSchemaAssetsExpression('/' . SQL_TABLE_PREFIX . '/');
        }

        return $config;
    }

    /**
     * @return \Doctrine\ORM\Configuration
     */
    public static function getBasicConfig()
    {
        // TODO we could use the tine20 redis cache here if configured (see \Doctrine\ORM\Tools\Setup::createConfiguration)
        // but as createConfiguration() tries to setup a redis cache if redis extension is available, we need to
        // setup a manual ArrayCache for the moment
        $cache = new \Doctrine\Common\Cache\ArrayCache();
        $config = Setup::createConfiguration(/* isDevMode = */ false, /* $proxyDir = */ null, $cache);
        return $config;
    }

    public static function getEntityManager()
    {
        $em = EntityManager::create(self::getDBParams(), self::getConfig());

        // needed to prevent runtime reflection that needs private properties ...
        $em->getMetadataFactory()->setReflectionService(new StaticReflectionService());

        return $em;
    }

    public static function getMetadata(array $modelNames)
    {
        $em = self::getEntityManager();

        $classes = array();
        foreach($modelNames as $modelName) {
            $classes[] = $em->getClassMetadata($modelName);
        }

        return $classes;
    }

    public static function createSchema(array $modelNames)
    {
        $em = self::getEntityManager();
        $tool = new SchemaTool($em);
        $classes = self::getMetadata($modelNames);

        $tool->createSchema($classes);
    }

    public static function updateSchema(array $modelNames)
    {
        $em = self::getEntityManager();
        $tool = new SchemaTool($em);
        $classes = self::getMetadata($modelNames);

        $tool->updateSchema($classes, true);
    }

    public static function updateAllSchema()
    {
        $em = self::getEntityManager();
        $tool = new SchemaTool($em);
        $classes = [];

        $mappingDriver = new Tinebase_Record_DoctrineMappingDriver();
        foreach($mappingDriver->getAllClassNames() as $modelName) {
            $classes[] = $em->getClassMetadata($modelName);
        }

        $tool->updateSchema($classes, true);
    }

    /**
     * compare two tine20 databases with each other
     *
     * @param $otherDbName
     * @return array of sql statements
     */
    public static function compareSchema($otherDbName, $otherUserName = null, $otherPassword = null)
    {
        $dbParams = self::getDBParams();

        $myConn = \Doctrine\DBAL\DriverManager::getConnection(
            $dbParams
        );
        $mySm = $myConn->getSchemaManager();

        $otherDbParams = $dbParams;
        $otherDbParams['dbname'] = $otherDbName;
        if (null !== $otherUserName) {
            $otherDbParams['user'] = $otherUserName;
        }
        if (null !== $otherPassword) {
            $otherDbParams['password'] = $otherPassword;
        }
        $otherConn = \Doctrine\DBAL\DriverManager::getConnection(
            $otherDbParams
        );
        $otherSm = $otherConn->getSchemaManager();

        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($mySm->createSchema(), $otherSm->createSchema());

        return $schemaDiff->toSaveSql($myConn->getDatabasePlatform());
    }
}
