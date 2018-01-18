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
class HostEntryController extends ApiMutableModelControllerBase
{
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
     * @param string|null $uuid item unique id
     * @return array
     */
    public function getHostEntryAction($host, $domain)
    {
        $match = Unbound::getHostEntryByFQDN($host, $domain);
        if($match == NULL) {
            return [];
        }
        // else
        return ["hostentry" => $match->toDts()];
    }


    public function delHostEntryAction($uuid)
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