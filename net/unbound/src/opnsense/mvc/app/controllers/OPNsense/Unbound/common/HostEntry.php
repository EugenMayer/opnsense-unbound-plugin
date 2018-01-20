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
    public $host;
    public $domain;
    public $rr = "A";
    public $ip = "";
    public $mxprio = "";
    public $mx = "";
    public $descr = "";


    public function toLegacy() {
        return (array)$this;
    }

    public function toDts() {
        return (array)$this;
    }

    /**
     * @param array $dts
     * @return HostEntry
     */
    static public function loadFromDTS($dts) {
        $hostEntry = new HostEntry();
        $hostEntryAttribs = array_keys(get_class_vars('OPNsense\Unbound\common\HostEntry'));
        foreach($hostEntryAttribs as $attrib) {
            if(isset($dts[$attrib])) {
                $hostEntry->{$attrib} = $dts[$attrib];
            }
        }

        return $hostEntry;
    }

    /**
     * @param array $legacyHostEntryArray
     * @return HostEntry
     */
    static public function loadFromLegacy($legacyHostEntryArray) {
        // for now those should match
        return self::loadFromDTS($legacyHostEntryArray);
    }

    /**
     * @param $dts
     * @return bool if true, validation was fine
     */
    static public function validateDTS($dts) {
        $mandatory = ['ip','host','domain'];
        foreach($mandatory as $attrib) {
            if(!isset($dts[$attrib])) {
                return false;
            }
        }
        return true;
    }
}