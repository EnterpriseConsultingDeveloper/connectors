<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\ShopifyConnector;

use App\Controller\MultiSchemaTrait;
use App\Lib\ActionsManager\ActionsManager;
use App\Lib\ActionsManager\Activities\ActivityEcommerceAddUserBean;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use Cake\ORM\TableRegistry;
use App\Lib\CRM\CRMManager;

class ShopifyCustomerConnector extends ShopifyConnector
{

    use MultiSchemaTrait;

    function __construct($params)
    {
        parent::__construct($params);
    }

    /**
     * @param $customerId
     * @param $params
     * @return boll
     * @add  28/11/2019  Fabio Mugnano <mugnano@enterprise-consulting.it>
     * @copyright (c) 2019, WhiteRabbit srl
     */
    public function read($customerId = null, $params = null)
    {
        $params_call = array();
        if (!empty($params['date'])) {
            $params_call['created_at_min'] = $params['date'];
        }
        $params_call['limit'] = $this->limitCall;
        \Cake\Log\Log::debug('Shopify ShopifyCustomerConnector call read on ' . $params['shop_url'] . ' params ' .  print_r(json_encode($params_call), true));
        try {
            $count_customer_db = $this->shopify->Customer->count($params_call);
        } catch (\Exception $e) {
            \Cake\Log\Log::debug('Shopify ShopifyCustomerConnector ERROR call count read on ' . $params['shop_url'] );
            return false;
        }
        $count_customer_crm = 0;
        \Cake\Log\Log::debug('Shopify ShopifyCustomerConnector call read on ' . $params['shop_url'] . ' count_customer_db ' . $count_customer_db);

        $customerResource = $this->shopify->Customer();
        $customers = $customerResource->get($params_call);
        $count_customer_crm += count($customers);
        \Cake\Log\Log::debug('Shopify ShopifyCustomerConnector call customers count ' . $count_customer_crm . ' on ' . $params['shop_url']);

        $nextPageCustomers = $customerResource->getNextPageParams();
        $nextPageCustomersArray = [];
        while ($nextPageCustomers) {
            $nextPageCustomersArray = $customerResource->get($customerResource->getNextPageParams());
            $count_customer_crm += count($nextPageCustomersArray);
            \Cake\Log\Log::debug('Shopify ShopifyCustomerConnector call count_customer_crm count ' . $count_customer_crm . ' on ' . $params['shop_url']);
            $customers = array_merge($customers, $nextPageCustomersArray);
            $nextPageCustomers = $customerResource->getNextPageParams();
        }

        foreach ($customers as $customer) {
            $data = [];
            $data['date'] = date('Y-m-d H:i:s', strtotime($customer['created_at']));
            $data['contact_code'] = $this->notSetToEmptyString($customer['id']);
            $data['name'] = $this->notSetToEmptyString($customer['first_name']);
            $data['surname'] = $this->notSetToEmptyString($customer['last_name']);
            $data['email'] = $this->notSetToEmptyString($customer['email']);
            $data['note'] = $this->notSetToEmptyString($customer['note']);
            $data['site_name'] = $this->notSetToEmptyString($this->shopUrl);
            $data['telephone1'] = $this->notSetToEmptyString($customer['phone']);
            if (!empty($customer['tags'])) {
                $dataTags = explode(',', $customer['tags']);
                foreach ($dataTags as $tag) {
                    $data['tags']['name'][] = $tag;
                }
            }
            $data['address'] = $this->notSetToEmptyString($customer['addresses'][0]['address1']);
            $data['city'] = $this->notSetToEmptyString($customer['addresses'][0]['city']);
            $data['nation'] = $this->notSetToEmptyString($customer['addresses'][0]['country_code']);
            $data['province'] = $this->notSetToEmptyString($customer['addresses'][0]['province_code']);
            $data['gdpr']['gdpr_marketing']['date'] = $this->notSetToEmptyString($customer['accepts_marketing_updated_at']);
            $data['gdpr']['gdpr_marketing']['value'] = ($customer['accepts_marketing'] == true) ? true : false;

            try {
                \Cake\Log\Log::debug('Shopify ShopifyCustomerConnector call ActivityEcommerceAddUserBean by ' . $data['email'] . ' on ' . $params['shop_url']);
                $this->createCrmConnection($customerId);
                $contactBean = new ActivityEcommerceAddUserBean();
                $contactBean->setCustomer($customerId)
                    ->setSource($this->shopUrl)
                    ->setToken($this->shopUrl)
                    ->setDataRaw($data);
								$contactBean->setTypeIdentities('email');

                ActionsManager::pushActivity($contactBean);

            } catch (\Exception $e) {
                // Log error
            }

        }
        return true;
    }

    /**
     * @param null $objectId
     *
     * @return array
     */
    public function Oldread($customerId = null)
    {
        $customers = $this->shopify->Customer->get();
        foreach ($customers as $customer) {

            $data = [];
            $data['date'] = date('Y-m-d H:i:s', strtotime($customer['created_at']));
            $data['externalid'] = $this->notSetToEmptyString($customer['id']);
            $data['name'] = $this->notSetToEmptyString($customer['first_name']);
            $data['surname'] = $this->notSetToEmptyString($customer['last_name']);
            $data['email'] = $this->notSetToEmptyString($customer['email']);
            $data['note'] = $this->notSetToEmptyString($customer['note']);
            $data['site_name'] = $this->notSetToEmptyString($this->shopUrl);
            $data['telephone1'] = $this->notSetToEmptyString($customer['phone']);
            $data['tags']['name'] = explode(',', $customer['tags']);
            $data['address'] = $this->notSetToEmptyString($customer['addresses'][0]['address1']);
            $data['city'] = $this->notSetToEmptyString($customer['addresses'][0]['city']);
            $data['nation'] = $this->notSetToEmptyString($customer['addresses'][0]['country_code']);
            $data['province'] = $this->notSetToEmptyString($customer['addresses'][0]['province_code']);
            $data['gdpr']['gdpr_marketing']['date'] = $this->notSetToEmptyString($customer['accepts_marketing_updated_at']);
            $data['gdpr']['gdpr_marketing']['value'] = ($customer['accepts_marketing'] == true) ? true : false;

            try {

                $this->createCrmConnection($customerId);
                $contactBean = new ActivityEcommerceAddUserBean();

                $contactBean->setCustomer($customerId)
                    ->setSource($this->shopUrl)
                    ->setToken($this->shopUrl)
                    ->setDataRaw($data);
								$contactBean->setTypeIdentities('email');

                ActionsManager::pushActivity($contactBean);

            } catch (\Exception $e) {
                // Log error
            }

        }
    }

    private function notSetToEmptyString(&$myString)
    {
        return (!isset($myString)) ? '' : $myString;
    }

}