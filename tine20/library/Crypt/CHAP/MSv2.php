<?php
/*
Copyright (c) 2002-2003, Michael Bretterklieber <michael@bretterklieber.com>
All rights reserved.
 
Redistribution and use in source and binary forms, with or without 
modification, are permitted provided that the following conditions 
are met:
 
1. Redistributions of source code must retain the above copyright 
   notice, this list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in the 
   documentation and/or other materials provided with the distribution.
3. The names of the authors may not be used to endorse or promote products 
   derived from this software without specific prior written permission.
 
THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND 
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED 
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. 
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, 
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, 
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY 
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING 
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, 
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 
This code cannot simply be copied and put under the GNU Public License or 
any other GPL-like (LGPL, GPL2) License.

    $Id$
*/

/**
 * class Crypt_CHAP_MSv2
 *
 * Generate MS-CHAPv2 Packets. This version of MS-CHAP uses a 16 Bytes authenticator 
 * challenge and a 16 Bytes peer Challenge. LAN-Manager responses no longer exists
 * in this version. The challenge is already a SHA1 challenge hash of both challenges 
 * and of the username.
 * 
 * @package Crypt_CHAP 
 */
class Crypt_CHAP_MSv2 extends Crypt_CHAP_MSv1
{
    /**
     * The username
     * @var  string
     */
    var $username = null;

    /**
     * The 16 Bytes random binary peer challenge
     * @var  string
     */
    var $peerChallenge = null;

    /**
     * The 16 Bytes random binary authenticator challenge
     * @var  string
     */
    var $authChallenge = null;
    
    /**
     * Constructor
     *
     * Generates the 16 Bytes peer and authentication challenge
     * @return void
     */
    function Crypt_CHAP_MSv2()
    {
        $this->Crypt_CHAP_MSv1();
        $this->generateChallenge('peerChallenge', 16);
        $this->generateChallenge('authChallenge', 16);
    }    

    /**
     * Generates a hash from the NT-HASH.
     *
     * @access public
     * @param  string  $nthash The NT-HASH
     * @return string
     */    
    function ntPasswordHashHash($nthash) 
    {
        return mhash(MHASH_MD4, $nthash);
    }
    
    /**
     * Generates the challenge hash from the peer and the authenticator challenge and
     * the username. SHA1 is used for this, but only the first 8 Bytes are used.
     *
     * @access public
     * @return string
     */   
    function challengeHash() 
    {
        return substr(mhash(MHASH_SHA1, $this->peerChallenge . $this->authChallenge . $this->username), 0, 8);
    }    

    /**
     * Generates the response. 
     *
     * @access public
     * @return string
     */  
    function challengeResponse() 
    {
        $this->challenge = $this->challengeHash();
        return $this->_challengeResponse();
    }    
}
