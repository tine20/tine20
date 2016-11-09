<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <M.Spahn@bitExpert.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * backend for TimeaccountFavorites
 *
 * @package     Timetracker
 * @subpackage  Backend
 */
class Timetracker_Backend_TimeaccountFavorites extends Tinebase_Backend_Sql_Abstract
{
    /**
     * @var bool
     */
    protected $_modlogActive = true;

    /**
     * @var string
     */
    protected $_modelName = 'Timetracker_Model_TimeaccountFavorite';

    /**
     * @var string
     */
    protected $_tableName = 'timetracker_timeaccount_fav';
}
