<?php

namespace OPNsense\Unbound\common {
    require_once("util.inc");
    // we are not allowed to include this one, otherwise phalconphp will not be able to load controllers anymore
    // see namespace below
    //require_once("config.inc");
    require_once("xmlparse.inc");
    require_once("interfaces.inc");
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
            global $config;
            $config = Config::getInstance()->toArray(listtags());
            unbound_hosts_generate();
        }


        /**
         * @param HostEntry $hostEntry
         * @param bool $generateUnboundConfig
         * @return bool
         */
        static function createHostEntryInConfig(HostEntry $hostEntry, $generateUnboundConfig = false)
        {
            // listtags is important, otherwise hosts will not be an array of hosts, but flatted
            $config = Config::getInstance()->toArray(listtags());
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
            // listtags is important, otherwise hosts will not be an array of hosts, but flatted
            $config = Config::getInstance()->toArray(listtags());
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
            // listtags is important, otherwise hosts will not be an array of hosts, but flatted
            $config = Config::getInstance()->toArray(listtags());
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
            $config = Config::getInstance()->toArray(listtags());
            if (!isset($config['unbound']['hosts'])) {
                return null;
            }

            foreach ($config['unbound']['hosts'] as $hostEntryLegacy) {
                // search all hosts for the entry we look for, host and domain must match
                if ($hostEntryLegacy['host'] == $host && $hostEntryLegacy['domain'] == $domain) {
                    return HostEntry::loadFromLegacy($hostEntryLegacy);
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
            // we cannot use toArray since hosts will be unserialized the wrong way
            $config = Config::getInstance()->toArray(listtags());
            if (!isset($config['unbound']['hosts'])) {
                return null;
            }

            foreach ($config['unbound']['hosts'] as $hostEntryLegacy) {
                // search all hosts for the entry we look for, host and domain must match
                if ($hostEntryLegacy['ip'] == $ip) {
                    return HostEntry::loadFromLegacy($hostEntryLegacy);
                }
            }

            return null;
        }


        /**
         * @return HostEntry[]
         */
        static function getLegacyHostEntries()
        {
            $config = Config::getInstance()->toArray(listtags());
            if (!isset($config['unbound']['hosts'])) {
                return [];
            }

            $hostEntries = [];
            foreach ($config['unbound']['hosts'] as $hostEntryLegacy) {
                // search all hosts for the entry we look for, host and domain must match
                $hostEntries[] = HostEntry::loadFromLegacy($hostEntryLegacy);
            }

            return $hostEntries;
        }
    }
}


/**
 * you may wonder why we need that crap. The point is, we cannot include config.php or phalconphp will break its loader
 * but on the other side we use the legacy method unbound_hosts_generate and thus those are called
 * - legacy_config_get_interfaces is called within unbound_hosts_generate
 * - config_read_array is called withing interfaces.php which is needed by  unbound_hosts_generate
 *
 * Thats dirty boy.
 */
namespace {
    /**
     * find list of registered interfaces
     * @param array $filters list of filters to apply
     * @return array interfaces
     */
    function legacy_config_get_interfaces($filters = array())
    {
        global $config;
        $interfaces = array();
        if (isset($config['interfaces'])) {
            foreach ($config['interfaces'] as $ifname => $iface) {
                // undo stupid listags() turning our item into a new array, preventing certain names to be used as interface.
                // see src/etc/inc/xmlparse.inc
                if (isset($iface[0])) {
                    $iface = $iface[0];
                }
                // apply filters
                $iface_match = true;
                foreach ($filters as $filter_key => $filter_value) {
                    if ($filter_key == 'enable' && isset($iface[$filter_key])) {
                        $field_value = true;
                    } else {
                        $field_value = isset($iface[$filter_key]) ? $iface[$filter_key] : false;
                    }
                    if ($field_value != $filter_value) {
                        $iface_match = false;
                        break;
                    }
                }
                if ($iface_match) {
                    $iface['descr'] = !empty($iface['descr']) ? $iface['descr'] : strtoupper($ifname);
                    $interfaces[$ifname] = $iface;
                }
            }
        }
        uasort($interfaces, function($a, $b) {
            return strnatcmp($a['descr'], $b['descr']);
        });
        return $interfaces;
    }

    function &config_read_array()
    {
        global $config;

        $current = &$config;

        foreach (func_get_args() as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = array();
            }
            $current = &$current[$key];
        }

        return $current;
    }
}

