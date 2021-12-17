<?php declare(strict_types=1);
/**
 * tine
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Offer Status Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_OfferStatus extends Tinebase_Config_KeyFieldRecord
{
    public const FLD_BOOKED = 'booked';
    public const FLD_CLOSED = 'closed';

    protected $_additionalValidators = [
        self::FLD_BOOKED => ['allowEmpty' => true ],
        self::FLD_CLOSED => ['allowEmpty' => true ],
    ];
}
