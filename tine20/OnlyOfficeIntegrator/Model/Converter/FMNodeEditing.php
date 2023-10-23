<?php
/**
 * Tine 2.0
 *
 * @package     OnlyOfficeIntegrator
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class OnlyOfficeIntegrator_Model_Converter_FMNodeEditing implements Tinebase_Model_Converter_RunOnNullInterface
{
    /**
     * @param Tinebase_Model_Tree_Node $record
     * @param $fieldName
     * @param $blob
     * @return bool
     */
    function convertToRecord($record, $fieldName, $blob)
    {
        if (Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $record->type) {
            return false;
        }
        if (($tokens = OnlyOfficeIntegrator_Controller_AccessToken::getInstance()->getUnresolvedTokensCached(true))->count() &&
            $tokens->filter(function(OnlyOfficeIntegrator_Model_AccessToken $token) use($record) {
                return !$token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_RESOLUTION} &&
                    $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_ID} === $record->getId() &&
                    $token->{OnlyOfficeIntegrator_Model_AccessToken::FLDS_NODE_REVISION} === $record->revision;
            })->count()
        ) {
            return true;
        }
        return false;
    }

    function convertToData($record, $fieldName, $fieldValue)
    {
        return $fieldValue;
    }
}
