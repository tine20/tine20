<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

# this code is based on https://github.com/Yubico/yubikey-ksm/blob/master/ykksm-utils.php
# Written by Simon Josefsson <simon@josefsson.org>.
# Copyright (c) 2009-2013 Yubico AB
# All rights reserved.
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions are
# met:
#
#   * Redistributions of source code must retain the above copyright
#     notice, this list of conditions and the following disclaimer.
#
#   * Redistributions in binary form must reproduce the above
#     copyright notice, this list of conditions and the following
#     disclaimer in the documentation and/or other materials provided
#     with the distribution.
#
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
# A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
# OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
# SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
# LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
# DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
# THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
# (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
# OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.


/**
 * Yubico Util class
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
final class Tinebase_Auth_MFA_YubicoUtil
{
    static public function yubi_hex2bin(string $h): string
    {
        $r = '';
        for ($a = 0; $a < strlen($h); $a += 2) {
            $r .= chr(hexdec($h[$a] . $h[($a + 1)]));
        }
        return $r;
    }

    static public function modhex2hex(string $m): string
    {
        return strtr($m, "cbdefghijklnrtuv", "0123456789abcdef");
    }

    static public function aes128ecb_decrypt(string $key, string $in): ?string
    {
        if (false === ($result = openssl_decrypt(self::yubi_hex2bin($in), 'AES-128-CBC', self::yubi_hex2bin($key),
                OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, self::yubi_hex2bin('00000000000000000000000000000000')))) {
            return null;
        }
        return bin2hex($result);
    }

    static public function calculate_crc(string $token): int
    {
        $crc = 0xffff;

        for ($i = 0; $i < 16; $i++) {
            $b = hexdec($token[$i * 2] . $token[($i * 2) + 1]);
            $crc = $crc ^ ($b & 0xff);
            for ($j = 0; $j < 8; $j++) {
                $n = $crc & 1;
                $crc = $crc >> 1;
                if ($n != 0) {
                    $crc = $crc ^ 0x8408;
                }
            }
        }
        return $crc;
    }

    static public function crc_is_good(string $token): bool
    {
        $crc = self::calculate_crc($token);
        return $crc === 0xf0b8;
    }
}
