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
#require_once("util.inc");
#require_once("plugins.inc.d/openvpn.inc");

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
     * @param HostEntry[] $staticCCDs
     * @param null $servers if null, it means all
     * @return void
     */
    static public function generateCCDconfigurationOnDisk($staticCCDs = NULL, $servers = null)
    {
        if ($staticCCDs == NULL) {
            $staticCCDs = self::getOpenVpnCCDs();
        }

        if ($servers == NULL) {
            $servers = self::getServers();
        }
        // since this whole thing should only "override" or generate those one, we defined
        // we do not work through the openvpn staticCCD but only ours
        // and either generate new ones or overwrite existing ones

        foreach ($staticCCDs as $ccd) {
            // now generate that CCD for every server
            foreach ($servers as $name => $server) {
                // that's a openvpn legacy tool to create CCDs for a specific server
                // lets use this to ensure compatibility
                $ccdConfigAsString = openvpn_csc_conf(self::ccdToLegacyStructure($ccd), $server);

                self::writeCCDforServer($ccd->common_name, $ccdConfigAsString, $server['vpnid']);
            }
        }
    }


    /**
     * This method is missing in the legacy API completely
     * @param string $common_name
     * @param string $openvpn_id
     */
    static function deleteCCDforServer($common_name, $openvpn_id)
    {
        $target_filename = "/var/etc/openvpn-csc/{$openvpn_id}/{$common_name}";
        @unlink($target_filename);
    }

    /**
     * This method is missing in the legacy API completely
     * @param string $common_name
     */
    static function deleteCCD($common_name)
    {
        $servers = self::getServers();
        foreach ($servers as $server) {
            self::deleteCCDforServer($common_name, $server['vpnid']);
        }
    }

    /**
     * @param $common_name
     * @return HostEntry
     */
    static public function getStaticCcd($common_name)
    {
        $staticCCDs = self::getOpenVpnCCDs();
        return $staticCCDs[$common_name];
    }


    /**
     * @return array an array of VPN-Servers ( stdClass ) which have the feature dynamic-ccd-lookup enabled
     */
    static function getServers()
    {
        $configObj = Config::getInstance()->object();
        $servers = array();

        if (isset($configObj->openvpn)) {
            /** @var \SimpleXMLElement $root */
            $root = $configObj->openvpn;
            foreach ($root->children() as $tag => $vpnServer) {
                // if that VPN server has dynamic ccd enabled
                if ($tag == 'openvpn-server') {
                    // ensured thats actually a openvpn server and now openvpn-csc .. they are in the same root node
                    $server = json_decode(json_encode($vpnServer), true);
                    $servers[] = $server;
                }
            }
        }

        return $servers;
    }


    /**
     * Writes the ccd configuration we created using the legacy method to the disk at the correct location for a specific server
     * @param string $common_name
     * @param string $ccdConfigAsString
     * @param string $openvpn_id
     */
    static function writeCCDforServer($common_name, $ccdConfigAsString, $openvpn_id)
    {
        openvpn_create_dirs();
        // 'stolen' from openvpn_configure_csc - we cannot reuse this function since its not designed to
        $target_filename = "/var/etc/openvpn-csc/{$openvpn_id}/{$common_name}";
        file_put_contents($target_filename, $ccdConfigAsString);
        chown($target_filename, 'nobody');
        chgrp($target_filename, 'nobody');
    }


    static function ccdToLegacyStructure(HostEntry $ccd)
    {
        return (array)$ccd;
    }

    /**
     * @return HostEntry[]
     */
    static function getOpenVpnCCDs()
    {
        $ccdsModel = new Ccd();

        $ccds = array();
        foreach ($ccdsModel->getNodes()['ccds']['ccd'] as $ccd) {
            $ccd = HostEntry::fromModelNode($ccd);
            $ccds[$ccd->common_name] = $ccd;
        }
        return $ccds;
    }
}