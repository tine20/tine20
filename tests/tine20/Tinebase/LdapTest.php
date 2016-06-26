<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Ldap
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_User_Ldap
 */
class Tinebase_LdapTest extends TestCase
{
    /**
     * @see 0011844: decodeSid fails for some encoded SIDs
     *
     * TODO write a test for a decoded sid, like this: ^A^E^@^@^@^@^@^E^U^@^@^@^A.z<F4>^W<B0>Ot^^<DC>^O^V<DE>-^@^@
     *
     * we could dump the encoded sid like this:
     *  $str = pack('c*', $data);
     *  for ($i=0; $i < strlen($str); ++$i) {
     *    echo '\x' . ord($str[$i]);
     *  }
     */
    public function testDecodeAlreadyDecodedSid()
    {
        $sid = "S-1-5-21-2127521184-1604012920-1887927527";

        $decodedSid = Tinebase_Ldap::decodeSid($sid);
        $this->assertEquals($decodedSid, $sid);
    }
}
