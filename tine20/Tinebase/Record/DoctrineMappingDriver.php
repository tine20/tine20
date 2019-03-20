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
use Doctrine\ORM\Mapping\ClassMetadataInfo;
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
        'stringAutocomplete' => 'string',
        'text'          => 'text',
        'fulltext'      => 'text',
        'datetime'      => 'datetime',
        'date'          => 'datetime',
        // TODO use datetime here?
        'time'          => 'time',
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
        'money'         => 'float',
        // TODO replace that with a single type 'datetime_separated'?
//        'datetime_separated' => 'date',
        'datetime_separated_date' => 'date',
        // not used yet:
        'datetime_separated_time' => 'time',
        'datetime_separated_tz' => 'string',
    );

    /**
     * Loads the metadata for the specified class into the provided container.
     *
     * @param string        $className
     * @param Doctrine\ORM\Mapping\ClassMetadata $metadata
     * @return void
     * @throws MappingException
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        if (! $this->isTransient($className)) {
            throw new MappingException('Class ' . $className . 'has no appropriate ModelConfiguration');
        }

        /** @var Tinebase_Record_Abstract $className */
        /** @var Tinebase_ModelConfiguration $modelConfig */
        $modelConfig = $className::getConfiguration();

        $table = $modelConfig->getTable();
        if (! isset($table['name'])) {
            throw new MappingException('Table name missing');
        }
        $table['name'] = SQL_TABLE_PREFIX . $table['name'];

        // mysql supports full text for InnoDB as of 5.6.4 for everybody else: remove full text index
        if ( ! Setup_Backend_Factory::factory()->supports('mysql >= 5.6.4 | mariadb >= 10.0.5') ||
                ! Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_FULLTEXT_INDEX)) {
            $this->_removeFullTextIndex($table);
        }

        $metadata->setPrimaryTable($table);

        $this->_mapAssociations($modelConfig, $metadata);
        $this->_mapFields($modelConfig, $metadata);
    }

    /**
     * @param Tinebase_ModelConfiguration $modelConfig
     * @param ClassMetadata $metadata
     */
    protected function _mapAssociations(Tinebase_ModelConfiguration $modelConfig, ClassMetadata $metadata)
    {
        foreach ($modelConfig->getAssociations() as $type => $associations) {
            foreach ($associations as $name => $association) {
                switch ($type) {
                    case ClassMetadataInfo::ONE_TO_ONE:
                        $metadata->mapOneToOne($association);
                        break;
                    case ClassMetadataInfo::MANY_TO_ONE:
                        $metadata->mapManyToOne($association);
                        break;
                    case ClassMetadataInfo::ONE_TO_MANY:
                        $metadata->mapOneToMany($association);
                        break;
                    case ClassMetadataInfo::MANY_TO_MANY:
                        $metadata->mapManyToMany($association);
                        break;
                }
            }
        }
    }

    /**
     * @param Tinebase_ModelConfiguration $modelConfig
     * @param ClassMetadata $metadata
     */
    protected function _mapFields(Tinebase_ModelConfiguration $modelConfig, ClassMetadata $metadata)
    {
        $virtualFields = array_keys($modelConfig->getVirtualFields());
        foreach ($modelConfig->getFields() as $fieldName => $config) {
            if (in_array($fieldName, $virtualFields, true)) {
                continue;
            }

            self::mapTypes($config);

            if (! $config['doctrineIgnore']) {
                try {
                    $metadata->mapField($config);
                } catch (\Doctrine\ORM\Mapping\MappingException $dome) {
                    // TODO ignore or fix exceptions like
                    //  "Property "id" in "Timetracker_Model_Timeaccount" was already declared,
                    //   but it must be declared only once"
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                        . ' ' . $dome->getMessage());

                }
            }
        }
    }

    /**
     * @param $table
     */
    protected function _removeFullTextIndex(&$table)
    {
        if (! isset($table['indexes'])) {
            return;
        }

        $toDelete = array();
        foreach ($table['indexes'] as $key => $index) {
            if (isset($index['flags']) && array_search('fulltext', $index['flags']) !== false) {
                $toDelete[] = $key;
            }
        }

        foreach ($toDelete as $key) {
            unset($table['indexes'][$key]);
        }
    }

    /**
     * map modelconfig type to doctrine type
     *
     * @param $config
     */
    public static function mapTypes(&$config)
    {
        // TODO associated foreign keys should be ignored by default
        $defaultDoctrineIgnore = isset($config['doctrineIgnore']) ? $config['doctrineIgnore'] : false;

        $config['doctrineIgnore'] = true;
        if (isset(self::$_typeMap[$config['type']])) {
            if ($config['type'] === 'container') {
                $config['length'] = 40;
            }
            $config['type'] = self::$_typeMap[$config['type']];
            $config['doctrineIgnore'] = $defaultDoctrineIgnore;
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
