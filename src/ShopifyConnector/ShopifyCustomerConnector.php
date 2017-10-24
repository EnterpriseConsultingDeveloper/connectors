<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\ShopifyConnector;

use WR\Connector\Connector;
use WR\Connector\ConnectorBean;
use WR\Connector\IConnector;
use Cake\Network\Http\Client;
use WR\Connector\ShopifyConnection;
use Cake\Collection\Collection;
use Abraham\ShopifyOAuth\ShopifyOAuth;
use PHPShopify;

class ShopifyOrderConnector extends ShopifyConnector
{

    function __construct($params)
    {
        parent::__construct($params);
    }


    /**
     * @param null $objectId
     * @return array
     */
    public function read($objectId = null)
    {
        $customers = $this->shopify->Customer->get();

        foreach($customers as $customer) {
            $data = [];
            $data['externalid'] = $this->notSetToEmptyString($customer['id']);
            $data['companyname'] = $this->notSetToEmptyString($customer['id']);
            $data['firstname'] = $this->notSetToEmptyString($customer['first_name']);
            $data['lastname'] = $this->notSetToEmptyString($customer['last_name']);
            $data['email1'] =  $this->notSetToEmptyString($customer['email']);
            $data['mobilephone1'] = $this->notSetToEmptyString($customer['phone']);
            $data['site_name'] = $this->notSetToEmptyString($this->shopUrl);
            $data['gender'] = '';

            $data['address'] = '';
            $data['city'] = '';
            $data['postalcode'] = '';
            $data['province'] = '';
            $data['birthdaydate'] = '';
            $data['telephone1'] = $this->notSetToEmptyString($customer['phone']);
            $data['taxcode'] = '';
            $data['nation'] = '';
            if($customer['accepts_marketing'] == true) {
                $data['newsletter_subscription_date'] = $this->notSetToEmptyString($customer['created_at']);
                $data['newsletter_subscription_ip'] = '';
            } else {
                $data['newsletter_subscription_date'] = '';
                $data['newsletter_subscription_ip'] = '';
            }

            $data['contact_typeid'] = '';

            try {
                //actionid =  $data['typeid']  . $data['operation']
                $crmManager = new CRMManager();
                $data['typeid'] = $crmManager::$ecommerceTypeId;
                $data['operation'] = $crmManager::$ecommerceActionAddUserId;

                $cmrRes = $crmManager->pushClientToCrm($customer['customer_id'], $data);

                return $cmrRes;
            } catch (\PDOException $e) {
                return false;
            }
        }
    }

    private function notSetToEmptyString (&$myString) {
        return (!isset($myString)) ? '' : $myString;
    }

}