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
    static public function generateHostEntriesOnDisk()
    {
        unbound_hosts_generate();
    }


    /**
     * @param HostEntry $hostEntry
     * @param bool $generateUnboundConfig
     * @return bool
     */
    static function createHostEntryInConfig(HostEntry $hostEntry, $generateUnboundConfig = false)
    {
        $config = Config::getInstance()->toArray();
        $config['unbound']['hosts'][] = $hostEntry->toLegacy();
        Config::getInstance()->fromArray($config);
        Config::getInstance()->save();

        if ($generateUnboundConfig) {
            self::generateHostEntriesOnDisk();
        }
        return true;
    }

    /**
     * @param HostEntry $hostEntry
     * @param bool $generateUnboundConfig
     * @return bool
     */
    static function updateHostEntryInConfig(HostEntry $hostEntry, $generateUnboundConfig = false)
    {
        $config = Config::getInstance()->toArray();
        for ($i = 0; $i <= count($config['unbound']['hosts']); $i++) {
            // search all hosts for the entry we look for, host and domain must match
            if ($config['unbound']['hosts'][$i]['host'] == $hostEntry->host
                && $config['unbound']['hosts'][$i]['domain'] == $hostEntry->domain) {
                $config['unbound']['hosts'][$i] = $hostEntry->toLegacy();
                Config::getInstance()->fromArray($config);
                Config::getInstance()->save();
                if ($generateUnboundConfig) {
                    self::generateHostEntriesOnDisk();
                }
                return true;
            }
        }
        return false;
    }


    /**
     * @param HostEntry $hostEntry
     * @param bool $generateUnboundConfig
     * @return bool
     */
    static function deleteHostEntryInConfig(HostEntry $hostEntry, $generateUnboundConfig = false)
    {
        $config = Config::getInstance()->toArray();
        for ($i = 0; $i <= count($config['unbound']['hosts']); $i++) {
            // search all hosts for the entry we look for, host and domain must match
            if ($config['unbound']['hosts'][$i]['host'] == $hostEntry->host
                && $config['unbound']['hosts'][$i]['domain'] == $hostEntry->domain) {
                unset($config['unbound']['hosts'][$i]);
                Config::getInstance()->fromArray($config);
                Config::getInstance()->save();
                if ($generateUnboundConfig) {
                    self::generateHostEntriesOnDisk();
                }
                return true;
            }
        }
        return false;
    }

    /**
     * @param $host
     * @param $domain
     * @return bool
     */
    static function existsHostEntryInConfig($host, $domain)
    {
        if (self::getHostEntryByFQDN($host, $domain) == null) {
            return false;
        }
        // else
        return true;
    }

    /**
     * @param $host
     * @param $domain
     * @return HostEntry
     */
    static function getHostEntryByFQDN($host, $domain)
    {
        $config = Config::getInstance()->toArray();
        foreach ($config['unbound']['hosts'] as $hostentry) {
            // search all hosts for the entry we look for, host and domain must match
            if ($hostentry['host'] == $host && $hostentry['domain'] == $domain) {
                return HostEntry::loadFromLegacy($hostentry);
            }
        }
        return null;
    }

    /**
     * @param $ip
     * @return HostEntry
     */
    static function getHostEntryByIp($ip)
    {
        $config = Config::getInstance()->toArray();
        foreach($config['unbound']['hosts'] as $hostentry) {
            // search all hosts for the entry we look for, host and domain must match
            if ($hostentry['ip'] == $ip) {
                return HostEntry::loadFromLegacy($hostentry);
            }
        }
        return null;
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
}