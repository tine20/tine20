<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class Tinebase_Setup_Update_14 extends Setup_Update_Abstract
{
    const RELEASE014_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_STRUCTURE   => [
            self::RELEASE014_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
        self::PRIO_TINEBASE_UPDATE      => [
        ]
    ];

    public function update001()
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_Tree_FileObject::class,
            Tinebase_Model_Tree_Node::class
        ]);

        $this->addApplicationUpdate('Tinebase', '14.1', self::RELEASE014_UPDATE001);
    }
}
