<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * virtual felamimail message pipe config model
 *
 * @package     Felamimail
 * @subpackage  Model
 *
 * @property string                                                         classname
 * @property Tinebase_Record_Interface|Tinebase_BL_ElementConfigInterface   configRecord
 */
class Felamimail_Model_MessagePipeConfig extends Tinebase_Model_BLConfig
{
    const MODEL_NAME_PART = 'MessagePipeConfig';
    const USER_RATING_SPAM = 'spam';
    const USER_RATING_HAM = 'ham';

    public static function factory(array $options) {

        if (!isset($options['config'])) {
            throw new Exception("strategy config is not set");
        }

        switch ($options['strategy']) {
            case 'copy':
                return new Felamimail_Model_MessagePipeCopy($options['config']);
                /* $options['config'] = ['target' => [array data]] */
                break;

            case 'move':
                return new Felamimail_Model_MessagePipeMove($options['config']);
                /* $options['config'] = ['target' => [array data]] */
                break;

            case 'rewrite_subject':
                return new Felamimail_Model_MessagePipeRewriteSubject($options['config']);
                /* $options['config'] = ['pattern' => '/^SPAM\? \(.+\) \*\*\* /',
                                         'replacement => ''] */
                break;

            default :
                throw new Exception('the strategy is not supported');
                break;
        }
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public static function inheritModelConfigHook(array &$_defintion)
    {
            $_defintion[self::APP_NAME] = Felamimail_Config::APPLICATION_NAME;
        $_defintion[self::MODEL_NAME] = self::MODEL_NAME_PART;
        if (!isset($_defintion[self::FIELDS][self::FLDS_CLASSNAME][self::CONFIG])) {
            $_defintion[self::FIELDS][self::FLDS_CLASSNAME][self::CONFIG] = [];
        }
        $_defintion[self::FIELDS][self::FLDS_CLASSNAME][self::CONFIG][self::AVAILABLE_MODELS] = [
            Felamimail_Model_MessagePipeCopy::class,
            Felamimail_Model_MessagePipeMove::class,
            Felamimail_Model_MessagePipeRewriteSubject::class,
        ];
    }
}