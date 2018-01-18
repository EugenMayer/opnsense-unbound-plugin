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
     * @param null $host
     * @param null $domain
     * @return array
     */
    public function getHostEntryAction($host = null, $domain = null)
    {
        if($host == null && $domain == null) {
            return Unbound::getLegacyHostEntries();
        }

        if($host == null && $domain != null || $host != null && $domain = null) {
            return ["result" => "failed", 'wrong_request' => "you need to set host and domain as url arguments, not just one, e.g: api/unbound/hostentry/getHostEntry/myhostname/mydomain.tld"];
        }
        $match = Unbound::getHostEntryByFQDN($host, $domain);
        if($match == NULL) {
            return [];
        }
        // else
        return ["hostentry" => $match->toDts()];
    }


    public function delHostEntryAction()
    {
        if ($this->request->isPost() && $this->request->hasPost("hostentry")) {
            $dts = $this->request->getPost("hostentry");

            if (!HostEntry::validateDTS($dts)) {
                return ["result" => "failed", 'validation' => "Not all mandatory fields set, you need to set host, domain and ip at least"];
            }
            $hostEntry = HostEntry::loadFromDTS($dts);
            if (Unbound::existsHostEntryInConfig($hostEntry->host, $hostEntry->domain)) {
                if (Unbound::deleteHostEntryInConfig($hostEntry, true)) {
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