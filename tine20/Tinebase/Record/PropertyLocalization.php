<?php declare(strict_types=1);

abstract class Tinebase_Record_PropertyLocalization extends Tinebase_Record_NewAbstract
{
    public const FLD_LANGUAGE = 'language';
    public const FLD_RECORD_ID = 'record_id';
    public const FLD_TEXT = 'text';
    public const FLD_TYPE = 'type';

    public const MODEL_NAME_PART = 'PropertyLocalization';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                       => 1,
        self::MODLOG_ACTIVE                 => true,
        self::IS_DEPENDENT                  => true,
        self::DEFAULT_SORT_INFO             => [
            self::FIELD                         => self::FLD_TEXT,
        ],

        self::TABLE                         => [
            self::NAME                          => '',
            self::UNIQUE_CONSTRAINTS            => [
                self::FLD_RECORD_ID                 => [
                    self::COLUMNS                       => [self::FLD_RECORD_ID, self::FLD_TYPE, self::FLD_LANGUAGE],
                ],
            ],
        ],

        self::ASSOCIATIONS                  => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                self::FLD_RECORD_ID             => [
                    self::TARGET_ENTITY             => '',
                    self::FIELD_NAME                => self::FLD_RECORD_ID,
                    self::JOIN_COLUMNS                  => [[
                        self::NAME                          => self::FLD_RECORD_ID,
                        self::REFERENCED_COLUMN_NAME        => 'id',
                        self::ON_DELETE                     => self::CASCADE,
                    ]],
                ],
            ],
        ],

        self::FIELDS                        => [
            self::FLD_RECORD_ID                 => [
                self::TYPE                          => self::TYPE_RECORD,
                self::CONFIG                        => [],
                self::DISABLED                      => true,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_TYPE                      => [
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 100,
                self::DISABLED                      => true,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_LANGUAGE                  => [
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 100,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_TEXT                      => [
                self::TYPE                          => self::TYPE_TEXT,
                self::LENGTH                        => (16 * 1024) - 1, // makes a TEXT 16K 4 byte chars, 64K bytes
                self::QUERY_FILTER                  => true,
            ],
        ],
    ];

    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        static $recursionShortCut = [];
        if (isset($recursionShortCut[static::class])) {
            return;
        }
        $staticClass = static::class;
        $recursionShortCut[$staticClass] = true;
        $raii = new Tinebase_RAII(function() use(&$recursionShortCut, $staticClass) {
            unset($recursionShortCut[$staticClass]);
        });

        parent::inheritModelConfigHook($_definition);

        /** @var Tinebase_Record_Interface $model */
        $model = null;
        if (preg_match('/^((.*)_Model_(.*))Localization$/', static::class, $m)) {
            $model = $m[1];
        } else {
            return; // or throw?
        }

        if (static::MODEL_NAME_PART !== $m[3] . 'Localization') {
            throw new Tinebase_Exception_Record_DefinitionFailure('MODEL_NAME_PART ' . static::MODEL_NAME_PART
                . ' is not ' . $m[3] . 'Localization');
        }
        $_definition[self::APP_NAME] = $m[2];
        $_definition[self::MODEL_NAME] = static::MODEL_NAME_PART;
        if (!array_key_exists(self::RECORD_NAME, $_definition)) {
            $_definition[self::RECORD_NAME] = 'Text'; // _('GENDER_Text')
            $_definition[self::RECORDS_NAME] = 'Texts'; // ngettext('Text', 'Texts', n)
        }
        if (!array_key_exists(self::TITLE_PROPERTY, $_definition)) {
            $_definition[self::TITLE_PROPERTY] = self::FLD_LANGUAGE;
        }
        if (empty($_definition[self::TABLE][self::NAME])) {
            $_definition[self::TABLE][self::NAME] = $model::getConfiguration()->getTableName() . '_localization';
        }
        $_definition[self::FIELDS][self::FLD_LANGUAGE] = array_replace_recursive(
            $_definition[self::FIELDS][self::FLD_LANGUAGE], $model::getConfiguration()->{self::LANGUAGES_AVAILABLE});

        if (empty($_definition[self::ASSOCIATIONS][\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE]
                [self::FLD_RECORD_ID][self::TARGET_ENTITY])) {
            $_definition[self::ASSOCIATIONS][\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE][self::FLD_RECORD_ID]
                [self::TARGET_ENTITY] = $model;
        }
        if (!array_key_exists(self::APP_NAME, $_definition[self::FIELDS][self::FLD_RECORD_ID][self::CONFIG])) {
            $_definition[self::FIELDS][self::FLD_RECORD_ID][self::CONFIG][self::APP_NAME] = $m[2];
        }
        if (!array_key_exists(self::MODEL_NAME, $_definition[self::FIELDS][self::FLD_RECORD_ID][self::CONFIG])) {
            $_definition[self::FIELDS][self::FLD_RECORD_ID][self::CONFIG][self::MODEL_NAME] = $m[3];
        }
        unset($raii);
    }
}
