<?php
/**
 * Created by Fabio Mugnano.
 * User: user
 * Date: 08/06/2017
 * Time: 15:31
 */

namespace WR\Connector\PrestashopConnector;


use Cake\ORM\TableRegistry;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use App\Lib\WhiteRabbit\WRClient;
use App\Lib\CRM\CRMManager;
use Cake\Log\Log;


class PrestashopConnector extends Connector implements IConnector
{
    protected $_http;
    protected $_psapipath;
    protected $_psuser;
    protected $_pspass;
    protected $_pstoken;

    private $objectId;
    private $ps;

    function __construct($params)
    {
        // Call Prestashop app

        $this->_http = new WRClient();
        $this->_psapipath = $params['apipath'];
        $this->_psuser = $params['username'];
        $this->_pspass = $params['password'];

        $connectPath = $this->_psapipath . 'connect';

        $response = $this->_http->post($connectPath, [
            'username' => $this->_psuser,
            'password' => $this->_pspass
        ]);

        $this->_pstoken = json_decode($response->body)->token;
    }

    public function connect($config)
    {
        if ($this->_pstoken != null) {
            $readPath = $this->_psapipath . 'connect';

            $response = $this->_http->get($readPath, [
                'q' => 'categories',
                'token' => $this->_pstoken
            ]);
            $bodyResp = json_decode($response->body(), true);
            if (isset($bodyResp['categories']))
                $wp_category = $bodyResp['categories'];
            if (isset($bodyResp['authors']))
                $wp_authors = $bodyResp['authors'];
        }
    }

    public function read($objectId = null)
    {


    }


    public function readPublicPage($objectId = null)
    {

    }


    public function update($content, $objectId)
    {
    }

    public function delete($objectId = null)
    {
        if ($this->_pstoken != null) {
            $publishPath = $this->_psapipath . 'delete';
            $response = $this->_http->post($publishPath, [
                'type' => 'post',
                'content_id' => $objectId,
                'token' => $this->_pstoken
            ]);
            $bodyResp = json_decode($response->body(), true);
            return $bodyResp['result'];
        } else {
            return false;
        }
    }

    public function mapFormData($data)
    {
        return $data;
    }

    public function stats($objectId)
    {

    }

    public function comments($objectId, $operation = 'r', $content = null)
    {

    }

    public function user($objectId)
    {

    }

    /**
     * @param $content
     * @return bool|mixed
     */
    public function add_user($content)
    {


        $data = [];
        $data['externalid'] = $this->notSetToEmptyString($content['customer_id']);
        $data['companyname'] = $this->notSetToEmptyString($content['customer_id']);
        $data['firstname'] = $this->notSetToEmptyString($content['name']);
        $data['lastname'] = $this->notSetToEmptyString($content['surname']);
        $data['email1'] = $this->notSetToEmptyString($content['email']);
        $data['mobilephone1'] = $this->notSetToEmptyString($content['mobilephone1']);
        $data['site_name'] = $this->notSetToEmptyString($content['site_name']);

        //extra


        $data['address'] = $this->notSetToEmptyString($content['address']);
        $data['city'] = $this->notSetToEmptyString($content['city']);
        $data['postalcode'] = $this->notSetToEmptyString($content['postalcode']);
        $data['province'] = $this->notSetToEmptyString($content['province']);
        $data['birthdaydate'] = $this->notSetToEmptyString($content['birthdaydate']);
        $data['telephone1'] = $this->notSetToEmptyString($content['telephone1']);
        $data['taxcode'] = $this->notSetToEmptyString($content['taxcode']);
        $data['nation'] = $this->notSetToEmptyString($content['nation']);
        //$data['operation'] = $this->notSetToEmptyString($content['operation']);
        $data['newsletter_subscription_date'] = $this->notSetToEmptyString($content['newsletter_subscription_date']);
        $data['newsletter_subscription_ip'] = $this->notSetToEmptyString($content['newsletter_subscription_ip']);
        $data['typeid'] = $this->notSetToEmptyString($content['typeid']);
        $data['contact_typeid'] = $this->notSetToEmptyString($content['contact_typeid']);

        try {
            //actionid =  $data['typeid']  . $data['operation']
            $crmManager = new CRMManager();
            $data['typeid'] = $crmManager::$ecommerceTypeId;
            $data['operation'] = $crmManager::$ecommerceActionAddUserId;
            $cmrRes = $crmManager->pushClientToCrm($content['customer_id'], $data);

            return $cmrRes;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function captureFan($objectId = null)
    {

    }

    public function update_categories($content)
    {

    }


    private function notSetToEmptyString(&$myString)
    {
        return (!isset($myString)) ? '' : $myString;
    }


    public function write($content)
    {

        $data = [];
        $data['orderIdExt'] = $this->notSetToEmptyString($content['orderIdExt']);
        $data['sourceId'] = $this->notSetToEmptyString($content['sourceId']);
        $data['orderNum'] = $this->notSetToEmptyString($content['orderNum']);
        $data['orderDate'] = $this->notSetToEmptyString($content['orderDate']);
        $data['orderTotal'] = $this->notSetToEmptyString($content['orderTotal']);
        $data['email'] = $this->notSetToEmptyString($content['email']);
        $data['orderState'] = $this->notSetToEmptyString($content['orderState']);
        $data['orderNote'] = $this->notSetToEmptyString($content['orderNote']);
        $data['site_name'] = $this->notSetToEmptyString($content['site_name']);
        $data['productActivity'] = $this->notSetToEmptyString(unserialize($content['productActivity']));
        try {
            $crmManager = new CRMManager();
            $cmrRes = $crmManager->pushOrderToCrm($content['customer_id'], $data);

            return $cmrRes;
        } catch (\PDOException $e) {
            return false;
        }
    }
}