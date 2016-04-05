<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use \Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Common\Persistence\Mapping\StaticReflectionService;
use \Doctrine\Common\Persistence\Mapping\Driver\StaticPHPDriver;

/**
 * helper around docrine2/dbal schema tools
 */
class Setup_SchemaTool
{
    /**
     * convert tine20config to dbal config
     *
     * @return array
     */
    public static function getDBParams()
    {
        $dbParams = Tinebase_Config::getInstance()->get('database')->toArray();
        $dbParams['driver'] = $dbParams['adapter'];
        $dbParams['user'] = $dbParams['username'];

        return $dbParams;
    }

    public static function getConfig($appName, $modelNames=null)
    {
        $mappingDriver = new Tinebase_Record_DoctrineMappingDriver();

        if (! $modelNames) {
            $modelNames = array();

            foreach($mappingDriver->getAllClassNames() as $modelName) {
                $modelConfig = $modelName::getConfiguration();

                if ($modelConfig->getApplName() == $appName) {
                    $modelNames[] = $modelName;
                }
            }
        }

        $tableNames = array();
        foreach($modelNames as $modelName) {
            $modelConfig = $modelName::getConfiguration();
            if (! $mappingDriver->isTransient($modelName)) {
                throw new Setup_Exception('Model not yet doctrine2 ready');
            }
            $tableNames[] = SQL_TABLE_PREFIX . Tinebase_Helper::array_value('name', $modelConfig->getTable());
        }

        $config = Setup::createConfiguration();
        $config->setMetadataDriverImpl($mappingDriver);

        $config->setFilterSchemaAssetsExpression('/'. implode('|',$tableNames) . '/');

        return $config;

    }

    public static function getEntityManager($appName, $modelNames=null)
    {
        $em = EntityManager::create(self::getDBParams(), self::getConfig($appName, $modelNames));

        // needed to prevent runtime reflection that needs private properties ...
        $em->getMetadataFactory()->setReflectionService(new StaticReflectionService());

        return $em;
    }

    public static function getMetadata($appName, $modelNames=null)
    {
        $em = self::getEntityManager($appName, $modelNames);

        $classes = array();
        foreach($modelNames as $modelName) {
            $classes[] = $em->getClassMetadata($modelName);
        }

        return $classes;
    }

    public static function createSchema($appName, $modelNames=null)
    {
        $em = self::getEntityManager($appName, $modelNames);
        $tool = new SchemaTool($em);
        $classes = self::getMetadata($appName, $modelNames);

        $tool->createSchema($classes);
    }

    public static function updateSchema($appName, $modelNames=null)
    {
        $em = self::getEntityManager($appName, $modelNames);
        $tool = new SchemaTool($em);
        $classes = self::getMetadata($appName, $modelNames);

        $tool->updateSchema($classes, true);
    }
}