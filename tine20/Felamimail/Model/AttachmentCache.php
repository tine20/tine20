<?php declare(strict_types=1);

/**
 * class to hold Attachment Cache data
 *
 * @package     Felamimail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold Attachment Cache data
 *
 * @package     Felamimail
 * @subpackage  Model
 */
class Felamimail_Model_AttachmentCache extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'AttachmentCache';
    const TABLE_NAME = 'felamimail_attachmentcache';

    const FLD_HASH = 'hash';
    const FLD_PART_ID = 'part_id';
    const FLD_SOURCE_ID = 'source_id';
    const FLD_SOURCE_MODEL = 'source_model';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                       => 1,
        self::MODLOG_ACTIVE                 => false,
        self::HAS_ATTACHMENTS               => true,
        self::SINGULAR_CONTAINER_MODE       => true,
        self::HAS_PERSONAL_CONTAINER        => false,

        self::APP_NAME                      => Felamimail_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::TABLE                         => [
            self::NAME                          => self::TABLE_NAME,
        ],

        self::FIELDS                        => [
            self::FLD_HASH                      => [
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
            ],
            self::FLD_PART_ID                   => [
                self::TYPE                          => self::TYPE_STRING,
                self::DOCTRINE_IGNORE               => true,
            ],
            self::FLD_SOURCE_ID                 => [
                self::TYPE                          => self::TYPE_STRING,
                self::DOCTRINE_IGNORE               => true,
            ],
            self::FLD_SOURCE_MODEL              => [
                self::TYPE                          => self::TYPE_STRING,
                self::DOCTRINE_IGNORE               => true,
            ],
        ]
    ];

    /*
     * api
     */

    public function isFSNode(): bool
    {
        if (Filemanager_Model_Node::class === $this->{self::FLD_SOURCE_MODEL} ||
                Tinebase_Model_Tree_Node::class === $this->{self::FLD_SOURCE_MODEL}) {
            return true;
        }
        return false;
    }

    /*
     * more or less internals (though some publics)
     */

    public static function modelConfigHook(array &$_definition)
    {
        $_definition['id']['length'] = 700;
    }

    public function hydrateFromBackend(array &$data)
    {
        parent::hydrateFromBackend($data);
        $this->fillFromId($data['id']);
    }

    public function setFromArray(array &$_data)
    {
        parent::setFromArray($_data);
        if (isset($_data['id'])) {
            $this->fillFromId($_data['id']);
        }
    }

    public function setId($_id)
    {
        parent::setId($_id);
        $this->fillFromId($_id);
    }

    protected function fillFromId(string $id)
    {
        [$model, $recordId, $partId] = explode(':', $id, 3);
        $this->{self::FLD_SOURCE_MODEL} = $model;
        $this->{self::FLD_SOURCE_ID} = $recordId;
        $this->{self::FLD_PART_ID} = $partId;
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}
