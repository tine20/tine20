<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Scheduler Task Model
 *
 * @package     Admin
 * @subpackage  Scheduler
 */

class Admin_Model_SchedulerTask_Import extends Admin_Model_SchedulerTask_Abstract
{
    public const MODEL_NAME_PART = 'SchedulerTask_Import';

    public const FLD_IMPORT_NAME = 'import_name';
    public const FLD_OPTIONS = 'options';
    public const FLD_PLUGIN_CLASS = 'plugin_class';

    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::modelConfigHook($_definition);

        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::FIELDS][self::FLD_IMPORT_NAME] = [
            self::TYPE          => self::TYPE_STRING,
            self::LABEL         => 'Name', // _('Name')
        ];
        $_definition[self::FIELDS][self::FLD_PLUGIN_CLASS] = [
            self::TYPE          => self::TYPE_STRING,
            self::LABEL         => 'Plugin', // _('Plugin')
        ];
        $_definition[self::FIELDS][self::FLD_OPTIONS] = [
            self::TYPE          => self::TYPE_JSON,
            self::LABEL         => 'Options', // _('Options')
        ];
    }

    public function run(): bool
    {
        if ($this->{self::FLD_IMPORT_NAME}) {
            $import = Tinebase_Import_Abstract::createFromDefinition(
                Tinebase_ImportExportDefinition::getInstance()->getByName($this->{self::FLD_IMPORT_NAME}),
                $this->{self::FLD_OPTIONS});
        } elseif ($this->{self::FLD_PLUGIN_CLASS} && class_exists($this->{self::FLD_PLUGIN_CLASS})) {
            $class = $this->{self::FLD_PLUGIN_CLASS};
            $import = new $class($this->{self::FLD_OPTIONS});
        } else {
            return false;
        }
        /** @var Tinebase_Import_Abstract $import */
        $import->import();

        return true;
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
