<?php

/*
 * Copyright (C) 2018 EugenMayer
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace OPNsense\Unbound\common;


class HostEntry
{
    public $common_name;
    // CIDR
    public $tunnel_network;
    // CIDR
    public $tunnel_networkv6;
    // CIDR
    public $local_network;
    // CIDR
    public $local_networkv6;
    // CIDR
    public $remote_network;
    // CIDR
    public $remote_network6;
    // redirect gateway
    public $gwredir;
    /**
     * if not empty, push will be reset. We aren`t using a boolean due to the legacy code
     */
    public $push_reset;
    /**
     * if not empty, client will be blocked. We aren`t using a boolean due to the legacy code
     */
    public $block = NULL;

    /**
     * @param string $netmask netmask as 255.255.255.0
     * @return int prefix like 24 for the above
     */
    static public function netmaskToCIDRprefix($netmask) {
        $long = ip2long($netmask);
        $base = ip2long('255.255.255.255');
        return 32-log(($long ^ $base)+1,2);
    }

    /**
     * @param array $ccdAsArray
     * @return HostEntry
     */
    static public function fromModelNode($ccdAsArray)
    {
        $ccd_attributes = array_keys(get_class_vars('OPNsense\OpenVpn\common\CcdDts'));

        $obj = (object) $ccdAsArray;
        $ccd = new HostEntry();

        // map all our legacy attributes on our helper class
        foreach ($ccd_attributes as $attr) {
            if (isset($obj->{$attr})) {
                $ccd->{$attr} = $obj->{$attr};
            }
        }

        return $ccd;
    }
}