<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\MappingException;

/**
 * Tinebase_Record_DoctrineMappingDriver
 *
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Record_DoctrineMappingDriver implements Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
{
    /**
     * @var array modelConfigType => Doctrine2Type
     */
    protected static $_typeMap = array(
        'string'        => 'string',
        'text'          => 'text',
        'fulltext'      => 'text',
        'datetime'      => 'datetime',
        'date'          => 'datetime',
        'time'          => 'datetime',
        'integer'       => 'integer',
        'numberableInt' => 'integer',
        'numberableStr' => 'string',
        'float'         => 'float',
        'json'          => 'text',
        'container'     => 'string',
        'record'        => 'string',
        'keyfield'      => 'string',
        'user'          => 'string',
        // NOTE 1: smallint is not working somehow ...
        // NOTE 2: we need int here because otherwise we need to typecast values for pgsql
        'boolean'       => 'integer',
        'money'         => 'float'
    );

    /**
     * Loads the metadata for the specified class into the provided container.
     *
     * @param string        $className
     * @param ClassMetadata $metadata
     *
     * @return void
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        if (! $this->isTransient($className)) {
            throw new MappingException('Class ' . $className . 'has no appropriate ModelConfiguration');
        }

        $modelConfig = $className::getConfiguration();

        $table = $modelConfig->getTable();
        $table['name'] = SQL_TABLE_PREFIX . $table['name'];

        // mysql supports full text for InnoDB as of 5.6.4 for everybody else: remove full text index
        if ( ! Setup_Backend_Factory::factory()->supports('mysql >= 5.6.4 | mariadb >= 10.0.5') ||
                ! Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_FULLTEXT_INDEX)) {
            if (isset($table['indexes'])) {
                $toDelete = array();
                foreach($table['indexes'] as $key => $index) {
                    if (isset($index['flags']) && array_search('fulltext', $index['flags']) !== false) {
                        $toDelete[] = $key;
                    }
                }

                foreach($toDelete as $key) {
                    unset($table['indexes'][$key]);
                }
            }
        }

        $metadata->setPrimaryTable($table);
        foreach ($modelConfig->getFields() as $fieldName => $config) {
            if (in_array($config, $modelConfig->getVirtualFields(), true)) {
                continue;
            }

            self::mapTypes($config);

            if (! $config['doctrineIgnore']) {
                $metadata->mapField($config);
            }
        }
    }

    /**
     * map modelconfig type to doctrine type
     *
     * @param $config
     */
    public static function mapTypes(&$config)
    {
        $config['doctrineIgnore'] = true;
        if (isset(self::$_typeMap[$config['type']])) {
            if ($config['type'] === 'container') {
                $config['length'] = 40;
            }
            $config['type'] = self::$_typeMap[$config['type']];
            $config['doctrineIgnore'] = false;
        }
    }

    /**
     * Gets the names of all mapped classes known to this driver.
     *
     * @return array The names of all mapped classes known to this driver.
     */
    public function getAllClassNames()
    {
        // @TODO Walk all models, check for modelconfig with version OR
        //       Walk all Controllers, ask for models and do the above
        return array();
    }

    /**
     * Returns whether the class with the specified name should have its metadata loaded.
     * This is only the case if it is either mapped as an Entity or a MappedSuperclass.
     *
     * @param string $className
     *
     * @return boolean
     */
    public function isTransient($className)
    {
        $modelConfig = $className::getConfiguration();

        return $modelConfig && is_int($modelConfig->getVersion());
    }
}
