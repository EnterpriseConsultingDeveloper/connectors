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
        \Cake\Log\Log::debug('Shopify ShopifyCustomerConnector call read on ' . $params['shop_url'] . ' params ' . print_r($params_call, true));
        try {
            $count_customer_db = $this->shopify->Customer->count($params_call);
        } catch (Exception $e) {
            $count_customer_db = 0;
        }
        \Cake\Log\Log::debug('Shopify ShopifyCustomerConnector call read on ' . $params['shop_url'] . ' count_customer_db ' . $count_customer_db);
        $exit = 0;
        $count_customer_crm = 0;
        $page = 0;
        while ($exit == 0) {
            $page++;
            $params_call['limit'] = $this->limitCall;
            $params_call['page'] = $page;
            $customers = $this->shopify->Customer->get($params_call);
            \Cake\Log\Log::debug('Shopify ShopifyCustomerConnector call read on ' . $params['shop_url'] . ' get_content_filter_data on params ' . print_r($params_call, true));
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

                    ActionsManager::pushActivity($contactBean);

                } catch (\Exception $e) {
                    // Log error
                }

            }
            $count_customer_crm += $this->limitCall;
            if ($count_customer_crm >= $count_customer_db) {
                $exit = 1;
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