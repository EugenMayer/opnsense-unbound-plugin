<?php

namespace OPNsense\Unbound\Api;


use \OPNsense\Base\ApiMutableModelControllerBase;
use OPNsense\Unbound\common\HostEntry;
use OPNsense\Unbound\common\Unbound;

/**
 * Class CcdController
 * @method array getNodes
 * @method setNodes
 * @property \Phalcon\Http\Request request
 * @package OPNsense\Unbound\Api
 */
class HostentryController extends ApiMutableModelControllerBase
{
    // keep those 2 ladies here, even though that model does not exist / we do not need it yet
    // otherwise the controller cannot get instantiated
    static protected $internalModelName = 'Ccd';
    static protected $internalModelClass = '\OPNsense\Unbound\Hostentry';
    /**
     * Endpoint : POST api/unbound/hostEntry/setHostEntry
     * Payload must look like this
     * {
     *   "hostentry": { "host":"newtest", "domain":"mydomain.tld","ip":"10.10.10.1".. }
     * }
     *
     * @return array
     */
    public function setHostEntryAction()
    {
        if ($this->request->isPost() && $this->request->hasPost("hostentry")) {
            $dts = $this->request->getPost("hostentry");

            if (!HostEntry::validateDTS($dts)) {
                return ["result" => "failed", 'validation' => "Not all mandatory fields set, you need to set host, domain and ip at least"];
            }
            $hostEntry = HostEntry::loadFromDTS($dts);
            if (Unbound::existsHostEntryInConfig($hostEntry->host, $hostEntry->domain)) {
                if (Unbound::updateHostEntryInConfig($hostEntry, true)) {
                    return array("result" => "updated");
                }
            } else {
                if (Unbound::createHostEntryInConfig($hostEntry, true)) {
                    return array("result" => "created");
                }
            }

        }
        return array("result" => "failed");
    }

    /**
     * Endpoint : GET api/unbound/hostEntry/getHostEntry
     * You can either query this API using
     * - no arguments ( all )
     * - GET arguments host and domain ( both )
     * - alternativly use as a path parameter <host>|<domain.tld> .. so api/unbound/hostEntry/getHostEntry/<host>|<domain.tld>
     *
     * This weired | splitted string is needed since only on parameter is supported on the path
     * @param string|null $hostDomain a string seperating the domain you used from the domain using a | .. so <host>|<domain.tld>
     * @return array
     */
    public function getHostEntryAction($hostDomain = null)
    {
        $host = $domain = null;

        if($hostDomain == null) {
            if (isset($_GET['host'])) {
                $host = $_GET['host'];
            }

            if (isset($_GET['domain'])) {
                $domain = $_GET['domain'];
            }
        }
        else {
            $parts = explode('|',$hostDomain);
            $host = $parts[0];
            $domain = $parts[1];
        }

        if($host == null && $domain == null) {
            return Unbound::getLegacyHostEntries();
        }

        if($host == null && $domain != null || $host != null && $domain == null) {
            return ["result" => "failed", 'wrong_request' => "you need to set host and domain as url arguments, not just one, e.g: api/unbound/hostentry/getHostEntry/myhostname/mydomain.tld"];
        }
        $match = Unbound::getHostEntryByFQDN($host, $domain);
        if($match == NULL) {
            return [];
        }
        // else
        return ["hostentry" => $match->toDts()];
    }

    /**
     * Endpoint : GET api/unbound/hostEntry/getHostEntryByIp
     *  - first parameter must be an ip api/unbound/hostEntry/getHostEntryByIp/<ip>
     *
     * same as getHostEntryAction, just by ip
     * @param string $ip
     * @return array
     */
    public function getHostEntryByIpAction($ip)
    {
        if($ip == null) {
            return Unbound::getLegacyHostEntries();
        }

        $match = Unbound::getHostEntryByIp($ip);
        if($match == NULL) {
            return [];
        }
        // else
        return ["hostentry" => $match->toDts()];
    }

    /**
     * Endpoint : POST api/unbound/hostEntry/delHostEntry
     *
     * Payload must look like this
     * {
     *   "hostentry": { "host":"newtest", "domain":"mydomain.tld"}
     * }
     *
     * Deletes the entry matching the host and domain
     *
     * @return array
     */
    public function delHostEntryAction()
    {
        if ($this->request->isPost() && $this->request->hasPost("hostentry")) {
            $dts = $this->request->getPost("hostentry");

            if (!isset($dts['host']) || !isset($dts['domain'])) {
                return ["result" => "failed", 'validation' => "Please set the host and domain field"];
            }
            $hostEntry = HostEntry::loadFromDTS($dts);
            if (Unbound::existsHostEntryInConfig($hostEntry->host, $hostEntry->domain)) {
                if (Unbound::deleteHostEntryInConfig($hostEntry, true)) {
                    return array("result" => "deleted");
                }
            }
            else {
                return array("result" => "not found");
            }

        }
        return array("result" => "failed");
    }

    /**
     * Endpoint : POST api/unbound/hostEntry/delHostEntryByIp
     *
     * Payload must look like this
     * {
     *   "hostentry": { "ip": "12.12.12.12"}
     * }
     * @return array
     */
    public function delHostEntryByIpAction()
    {
        if ($this->request->isPost() && $this->request->hasPost("hostentry")) {
            $dts = $this->request->getPost("hostentry");

            if (!isset($dts['ip'])) {
                return ["result" => "failed", 'validation' => "Please set the host entry IP"];
            }
            $match = Unbound::getHostEntryByIp($dts['ip']);
            if ($match!= null) {
                if (Unbound::deleteHostEntryInConfig($match, true)) {
                    return array("result" => "updated");
                }
            }
            else {
                return array("result" => "not found");
            }

        }
        return array("result" => "failed");
    }
}