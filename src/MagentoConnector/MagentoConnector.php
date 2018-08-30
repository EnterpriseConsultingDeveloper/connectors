<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\MagentoConnector;


use Cake\ORM\TableRegistry;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use App\Lib\WhiteRabbit\WRClient;
use App\Lib\CRM\CRMManager;
use Cake\Log\Log;


class MagentoConnector extends Connector implements IConnector
{
    protected $_http;
    protected $_mageapipath;
    protected $_mageuser;
    protected $_magepass;
    protected $_magetoken;

    private $objectId;
    private $mage;

    function __construct($params)
    {
        // Call Magento app
        $this->_http = new WRClient();
        $this->_mageapipath = $params['apipath'];
        $this->_mageuser = $params['username'];
        $this->_magepass = $params['password'];

        //Log::write('debug', "magento " . serialize($params));

        $connectPath = $this->_mageapipath . 'connect';


        $response = $this->_http->post($connectPath, [
            'username' => $this->_mageuser,
            'password' => $this->_magepass
        ]);


        $this->_magetoken = json_decode($response->body)->token;


        //$this->_magetoken="ciao";

    }

    public function connect($config)
    {
        if ($this->_magetoken != null) {
            $readPath = $this->_wpapipath . 'connect';

            $response = $this->_http->get($readPath, [
                'q' => 'categories',
                'token' => $this->_magetoken
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
//        $response = $this->_http->get('http://google.com/search', ['q' => 'widget'], [
//            'headers' => ['X-Requested-With' => 'XMLHttpRequest']
//        ]);

    }


    public function readPublicPage($objectId = null)
    {

    }

    /**
     * @return array
     */
    /*   public function write($content)
       {
           $publishPath = $this->_mageapipath . '/publish';
           $response = $this->_http->post($publishPath, [
               'type' => 'newsletter',
               'content' => '',
               'content_id' => ''
           ]);

           return false;

       }*/

    public function update($content, $objectId)
    {
    }

    public function delete($objectId = null)
    {
        if ($this->_magetoken != null) {
            $publishPath = $this->_mageapipath . 'delete';
            $response = $this->_http->post($publishPath, [
                'type' => 'post',
                'content_id' => $objectId,
                'token' => $this->_magetoken
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
        //It's not correct to implement this here. Trying to find a different solutions
//        $nlRecipientLists = TableRegistry::get('MarketingTools.MtNewsletterRecipientLists');
//        $listId = null;
//        if (isset($content['list_name'])) {
//            $listId = $nlRecipientLists->saveFromConnector(
//                $content['list_name'], $content['customer_id']);
//        }
//
//        $nlRecipients = TableRegistry::get('MarketingTools.MtNewsletterRecipients');
//        $nlRecipient = $nlRecipients->newEntity();

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

            $crmManager->setCustomer($content['customer_id']);
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
        //It's not correct to implement this here. Trying to find a different solutions
        $ciChannels = TableRegistry::get('ConnectorInstanceChannels');
        $ciChannel = $ciChannels->find('all')
            ->where(['ConnectorInstanceChannels.id' => $content['connector_instance_channel_id']])
            ->first();

        if ($ciChannel && isset($content['categories_tree'])) {
            $ciChannel->categories_tree = json_encode($content['categories_tree']);
            try {
                return $ciChannels->save($ciChannel);
            } catch (\PDOException $e) {
                return false;
            }
        }

        return false;
    }


    private function notSetToEmptyString(&$myString)
    {
        return (!isset($myString)) ? '' : $myString;
    }


    public function write($content)
    {
        /*$data = array(
            'orderIdExt' => '100',
            'sourceId' => 'magento',
            'orderNum' => '100',
            'orderDate' => '2016-11-30',
            'orderTotal' => '100.10'
        );*/
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
            $crmManager->setCustomer($content['customer_id']);
            $cmrRes = $crmManager->pushOrderToCrm($content['customer_id'], $data);

            return $cmrRes;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function setError($message) {

    }
}
