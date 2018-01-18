<?php

namespace OPNsense\Unbound\Api;


use \OPNsense\Base\ApiMutableModelControllerBase;
use \OPNsense\Core\Config;
use \OPNsense\Openvpn\Ccd;
use OPNsense\Openvpn\common\CcdDts;
use OPNsense\Openvpn\common\Unbound;

/**
 * Class CcdController
 * @method \OPNsense\Openvpn\Ccd getModel
 * @method array getNodes
 * @method setNodes
 * @property \Phalcon\Http\Request request
 * @package OPNsense\Openvpn\Api
 */
class HostEntryController extends ApiMutableModelControllerBase
{
    static protected $internalModelName = 'Ccd';
    static protected $internalModelClass = '\OPNsense\Openvpn\Ccd';

    /**
     * Payload must look like this
     * {
     *   "ccd": { "common_name":"newtest" }
     * }
     *
     * If uuid is given, operates as update but ensures name is unique ( fails otherwise )
     * if uud is omited, operates as create
     * @param string|null $uuid item unique id
     * @return array
     */
    public function setCcdAction($uuid = null)
    {
        if ($this->request->isPost() && $this->request->hasPost("ccd")) {
            if ($uuid != null) {
                $node = $this->getModel()->getNodeByReference("ccds.ccd.$uuid");
            } else {
                /** @var \OPNsense\Openvpn\Ccd $node */
                $node = $this->getModel()->ccds->ccd->Add();
            }

            $data = $this->request->getPost("ccd");
            if ($this->getModel()->getUuidByCcdName($data['common_name']) == NULL) {
                $node->setNodes($data);
                $result = $this->validateAndSave($node, 'ccd');
                Unbound::generateCCDconfigurationOnDisk([CcdDts::fromModelNode($data)]);
                $result['modified_uuid'] = $this->getModel()->getUuidByCcdName($data['common_name']);
                return $result;
            } else {
                return ["result" => "failed", 'validation' => "a ccd with the name '{$data['common_name']}' already exists"];
            }
        }
        return array("result" => "failed");
    }

    /**
     * Payload must look like this
     * {
     *   "ccd": { "common_name":"newtest" }
     * }
     *
     * in comparison to setCcdAction this method tries to find your given CCD by name
     * it does find it, it rather does a update, otherwise and insert.
     * So this will automatically update if a name matches an existing entry or create
     * if that name yet does not exist
     *
     * @param string|null $uuid item unique id
     * @return array
     */
    public function setCcdByNameAction()
    {
        if ($this->request->isPost() && $this->request->hasPost("ccd")) {
            $data = $this->request->getPost("ccd");
            $lookupUuid = $this->getModel()->getUuidByCcdName($data['common_name']);
            if ($lookupUuid == NULL) {
                // create case
                $node = $this->getModel()->ccds->ccd->Add();
                $node->setNodes($data);
            } else {
                // update case
                $node = $this->getModel()->getNodeByReference("ccds.ccd.$lookupUuid");
                $node->setNodes($data);
            }
            $result = $this->validateAndSave($node, 'ccd');
            Unbound::generateCCDconfigurationOnDisk([CcdDts::fromModelNode($data)]);
            $result['modified_uuid'] = $this->getModel()->getUuidByCcdName($data['common_name']);
            return $result;
        }
        return array("result" => "failed");
    }

    /**
     * @param string|null $uuid item unique id
     * @return array
     */
    public function getCcdAction($uuid = null)
    {
        if ($uuid == null) {
            // list all
            return array($this->getModel()->getNodes());
        } else {
            $node = $this->getModel()->getNodeByReference('ccds.ccd.' . $uuid);
            if ($node != null) {
                // return node
                return array("ccd" => $node->getNodes());
            }
        }
        return array();
    }


    public function delCcdAction($uuid)
    {
        $result = array('result' => 'failed');
        if ($this->request->isPost()) {
            $node = $this->getModel()->getNodeByReference("ccds.ccd.$uuid");
            if ($node == NULL) {
                return [];
            }

            $ccd = CcdDts::fromModelNode($node->getNodes());
            Unbound::deleteCCD($ccd->common_name);
            if ($this->getModel()->ccds->ccd->del($uuid)) {
                $result = $this->validateAndSave();
                $result['modified_uuid'] = $uuid;
                return $result;
            }

            return [];
        }
        return $result;
    }
}