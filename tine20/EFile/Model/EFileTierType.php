<?php
/**
 * class to hold EFileTierType data
 *
 * @package     EFile
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold EFileTierType data
 *
 * @package     EFile
 * @subpackage  Model
 */
class EFile_Model_EFileTierType extends Tinebase_Config_KeyFieldRecord
{
    const MODEL_NAME_PART = 'EFileTierType';

    const TIER_TYPE_CASE = 'case';
    const TIER_TYPE_DOCUMENT = 'document';
    const TIER_TYPE_DOCUMENT_DIR = 'documentDir';
    const TIER_TYPE_FILE = 'file';
    const TIER_TYPE_FILE_GROUP = 'fileGroup';
    const TIER_TYPE_MASTER_PLAN = 'masterPlan';
    // attention, not an actual type, only used for config root prefix
    const TIER_TYPE_MASTER_PLAN_ROOT = 'masterPlanRoot';
    const TIER_TYPE_SUB_FILE = 'subFile';
}
