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
    public function read($customerId = null)
    {
        $orders = $this->shopify->Order->get();

        foreach($orders as $order) {
            $data = [];
            $data['orderIdExt'] = $this->notSetToEmptyString($order['id']);
            $data['sourceId'] = $this->notSetToEmptyString($this->shopUrl);
            $data['orderNum'] = $this->notSetToEmptyString($order['order_number']);
            $data['orderDate'] = $this->notSetToEmptyString($order['created_at']);
            $data['orderTotal'] =  $this->notSetToEmptyString($order['total_price']);
            $data['email'] =  $this->notSetToEmptyString($order['email']);
            $data['orderState'] =  $this->notSetToEmptyString($order['confirmed']);
            $data['orderNote'] =  $this->notSetToEmptyString($order['note']);
            $data['site_name'] = $this->notSetToEmptyString($this->shopUrl);

            $productActivity = null;
            foreach ($order['line_items'] as $key => $item) {
                $productActivity[$key]['product_id'] = $item['id'];
                $productActivity[$key]['qty'] = $item['quantity'];
                $productActivity[$key]['name'] = $item['title'];
                $productActivity[$key]['price'] = $item['price'];
                $productActivity[$key]['discount'] = $item['total_discount'];
            }

            $data['productActivity'] = $productActivity;
            try {
                $crmManager = new CRMManager();
                $cmrRes = $crmManager->pushOrderToCrm($customerId, $data);

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