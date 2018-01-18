<?php

/*
 * Copyright (C) 2018 Eugen Mayer
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
// yeah, why should plugins.inc.d/openvpn.inc include all the symbols it is using..
require_once("util.inc");
require_once("plugins.inc.d/unbound.inc");

use \OPNsense\Core\Config;
use \OPNsense\Openvpn\Ccd;

/**
 * Handles all kind of OpenVPN based operations
 * Class OpenVpn
 * @package OPNsense\Freeradius\common
 */
class Unbound
{
    /**
     * @return void
     */
    static public function generateHostEntries()
    {
        unbound_hosts_generate();
    }


    static function addHostEntryInConfig(HostEntry $hostEntry)
    {
        // TODO
    }

    static function updateHostEntryInConfig(HostEntry $hostEntry)
    {
        // TODO
    }

    static function deleteHostEntryInConfig(HostEntry $hostEntry)
    {
        // TODO
    }

    /**
     * @return HostEntry[]
     */
    static function getLegacyHostEntries()
    {
        $configObj = Config::getInstance()->object();
        if (isset($configObj->unbound) && isset($configObj->unbound->{'hosts'})) {
            $hostEntries = array();
            $hostEntryAttribs = array_keys(get_class_vars('OPNsense\Unbound\common\HostEntry'));
            // odd need of parsing them here, otherwise the result gets oddly transpiled
            foreach ($configObj->unbound->{'hosts'} as $hostEntryXML) {
                $obj = json_decode(json_encode($hostEntryXML));
                $hostEntry = new HostEntry();
                // map all our legacy attributes on our helper class
                foreach ($hostEntryAttribs as $attr) {
                    if (isset($obj->{$attr})) {
                        $hostEntry->{$attr} = $obj->{$attr};
                    }
                }
                $hostEntries[$hostEntry->common_name] = $hostEntry;
            }
            return $hostEntries;
        }
        return NULL;
    }

    static function hostEntryToLegacyStructure(HostEntry $hostEntry)
    {
        return (array)$hostEntry;
    }
}