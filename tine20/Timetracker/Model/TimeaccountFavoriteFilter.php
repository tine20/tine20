<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Timeaccount favorite filter Class
 *
 * @package Timetracker
 */
class Timetracker_Model_TimeaccountFavoriteFilter extends Tinebase_Model_Filter_FilterGroup implements Tinebase_Model_Filter_AclFilter
{
    /**
     * @var string
     */
    protected $_applicationName = 'Timetracker';

    /**
     * @var string
     */
    protected $_modelName = TimeaccountFavorite::class;

    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'account_id'      => array('filter' => 'Tinebase_Model_Filter_Id'),
        'timeaccount_id'  => array('filter' => 'Tinebase_Model_Filter_Id'),
        'id'              => array('filter' => 'Tinebase_Model_Filter_Id'),
    );
}
