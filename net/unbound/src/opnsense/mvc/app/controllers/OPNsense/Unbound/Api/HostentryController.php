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
     */
    public function setHostEntryAction()
    {
        if ($this->request->isPost() && $this->request->hasPost("hostentry")) {
            $dts = $this->request->getPost("hostentry");

            if (!HostEntry::validateDTS($dts)) {
                http_response_code(400);
                $this->returnError("Not all mandatory fields set, you need to set host, domain and ip at least");
            }
            $hostEntry = HostEntry::loadFromDTS($dts);
            if (Unbound::existsHostEntryInConfig($hostEntry->host, $hostEntry->domain)) {
                if (Unbound::updateHostEntryInConfig($hostEntry, true)) {
                    $this->returnData(["fqdn" => "{$hostEntry->host}.{$hostEntry->domain}","op" => "update"]);
                }
            } else {
                if (Unbound::createHostEntryInConfig($hostEntry, true)) {
                    $this->returnData(["fqdn" => "{$hostEntry->host}.{$hostEntry->domain}","op" => "created"]);
                }
            }

        }

        http_response_code(500);
        $this->returnError("only POST is allowed");
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
            // with 19.1 those are url encoded now
            $hostDomain = urldecode($hostDomain);
            $parts = explode('|',$hostDomain);
            $host = $parts[0];
            $domain = $parts[1];
        }

        if($host == null && $domain == null) {
            $this->returnData(Unbound::getLegacyHostEntries());
        }

        if($host == null && $domain != null || $host != null && $domain == null) {
            http_response_code(400);
            $this->returnError("you need to set host and domain as url arguments, not just one, e.g: api/unbound/hostentry/getHostEntry/myhostname/mydomain.tld");
        }
        $match = Unbound::getHostEntryByFQDN($host, $domain);
        if($match == NULL) {
            http_response_code(404);
            $this->returnError("not found");
        }
        // else
        $this->returnData($match->toDts());
    }

    /**
     * Endpoint : GET api/unbound/hostEntry/getHostEntryByIp
     *  - first parameter must be an ip api/unbound/hostEntry/getHostEntryByIp/<ip>
     *
     * same as getHostEntryAction, just by ip
     * @param string $ip
     */
    public function getHostEntryByIpAction($ip)
    {
        if($ip == null) {
            $this->returnData(Unbound::getLegacyHostEntries());
        }

        $match = Unbound::getHostEntryByIp($ip);
        if($match == NULL) {
            http_response_code(404);
            $this->returnError("not found");
        }
        // else
        $this->returnData($match->toDts());
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
     */
    public function delHostEntryAction()
    {
        if ($this->request->isPost() && $this->request->hasPost("hostentry")) {
            $dts = $this->request->getPost("hostentry");

            if (!isset($dts['host']) || !isset($dts['domain'])) {
                http_response_code(400);
                $this->returnError("Please set the host and domain in the entry");
            }
            $hostEntry = HostEntry::loadFromDTS($dts);
            if (Unbound::existsHostEntryInConfig($hostEntry->host, $hostEntry->domain)) {
                if (Unbound::deleteHostEntryInConfig($hostEntry, true)) {
                    $this->returnData(["fqdn" => "{$hostEntry->host}.{$hostEntry->domain}"]);
                }
            }
            else {
                http_response_code(404);
                $this->returnError("not found");
            }

        }
        http_response_code(500);
        $this->returnError("only POST is allowed");
    }

    /**
     * Endpoint : POST api/unbound/hostEntry/delHostEntryByIp
     *
     */
    public function delHostEntryByIpAction()
    {
        if ($this->request->isPost() && $this->request->hasPost("hostentry")) {
            $dts = $this->request->getPost("hostentry");

            if (!isset($dts['ip'])) {
                http_response_code(400);
                $this->returnError("Please set the host entry IP");
            }
            $hostEntry = Unbound::getHostEntryByIp($dts['ip']);
            if ($hostEntry!= null) {
                http_response_code(404);
                if (Unbound::deleteHostEntryInConfig($hostEntry, true)) {
                    $this->returnData(["fqdn" => "{$hostEntry->host}.{$hostEntry->domain}"]);
                }
                else {
                    http_response_code(404);
                    $this->returnError("not found");
                }
            }
            else {
                http_response_code(404);
                $this->returnError("not found");
            }

        }

        http_response_code(500);
        $this->returnError("only POST is allowed");
    }

    private function returnData($data) {
        $response = new \stdClass();
        $response->data = $data;
        $response->status = 'success';
        header('Content-type: application/json');
        echo json_encode($response);
        exit(0);
    }

    private function returnError($message) {
        $response = new \stdClass();
        $response->status = "error";
        $response->message = $message;
        header('Content-type: application/json');
        echo json_encode($response);
        exit(0);
    }
}