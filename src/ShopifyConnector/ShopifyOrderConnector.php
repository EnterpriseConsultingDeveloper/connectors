<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\ShopifyConnector;

use App\Controller\Component\UtilitiesComponent;
use App\Controller\MultiSchemaTrait;
use App\Lib\ActionsManager\ActionsManager;
use App\Lib\ActionsManager\Activities\ActivityEcommerceAddUserBean;
use App\Lib\ActionsManager\Activities\ActivityEcommerceChangeStatusBean;
use Cake\I18n\Time;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use Cake\ORM\TableRegistry;
use App\Lib\CRM\CRMManager;

class ShopifyOrderConnector extends ShopifyConnector
{
    use MultiSchemaTrait;

    function __construct($params)
    {
        parent::__construct($params);
    }


    /**
     * @param null $objectId
     * @return array
     */
    public function read($customerId = null, $params = null)
    {
        $params_call = array();
        $params_call['status'] = "any";
        $params_call['limit'] = $this->limitCall;
        if (!empty($params['date'])) {
            $params_call['created_at_min'] = $params['date'];
        }
        \Cake\Log\Log::debug('Shopify ShopifyOrderConnector call read on ' . $params['shop_url'] . ' params ' . print_r(json_encode($params_call), true));
        try {
            $count_order_db = $this->shopify->Order->count($params_call);
        } catch (\Exception $e) {
            \Cake\Log\Log::debug('Shopify ShopifyOrderConnector ERROR call count read on ' . $params['shop_url'] );
            return false;
        }

        \Cake\Log\Log::debug('Shopify ShopifyOrderConnector call read on ' . $params['shop_url'] . ' count_order_db ' . $count_order_db);
        $orderResource = $this->shopify->Order();
        $orders = $orderResource->get($params_call);
        $count_order_crm = count($orders);
        \Cake\Log\Log::debug('Shopify ShopifyOrderConnector call orders count ' . $count_order_crm . ' on ' . $params['shop_url']);

        $nextPageOrders = $orderResource->getNextPageParams();
        $nextPageOrdersArray = [];
        while ($nextPageOrders) {
            $nextPageOrdersArray = $orderResource->get($orderResource->getNextPageParams());
            $count_order_crm += count($nextPageOrdersArray);
            \Cake\Log\Log::debug('Shopify ShopifyOrderConnector call count_order_crm count ' . $count_order_crm . ' on ' . $params['shop_url']);
            $orders = array_merge($orders, $nextPageOrdersArray);
            $nextPageOrders = $orderResource->getNextPageParams();
        }

        foreach ($orders as $order) {
            \Cake\Log\Log::debug('Shopify ShopifyOrderConnector import order_number ' . $order['order_number']);
            $data = [];
            $data['source'] = $this->shopUrl;
            $data['email'] = $this->notSetToEmptyString($order['email']);
            $data['number'] = $this->notSetToEmptyString($order['order_number']);
            $data['orderdate'] = $order['created_at'];
            $data['order_status'] = $this->notSetToEmptyString($order['financial_status']);
            $data['total'] = $this->notSetToEmptyString($order['total_price']);
            $data['currency'] = $this->notSetToEmptyString($order['currency']);
            $data['tax_total'] = $this->notSetToEmptyString($order['total_tax']);
            $data['subtotal'] = $this->notSetToEmptyString($order['subtotal_price']);
            $data['cart_discount'] = $this->notSetToEmptyString($order['total_discounts']);

//          $data['shipping_total'] = $this->notSetToEmptyString($content['shipping_total']);
            $data['shipping_firstname'] = $this->notSetToEmptyString($order['shipping_address']['shipping_firstname']);
            $data['shipping_lastname'] = $this->notSetToEmptyString($order['shipping_address']['last_name']);
            $data['shipping_address'] = $this->notSetToEmptyString($order['shipping_address']['address1']);
            $data['shipping_postalcode'] = $this->notSetToEmptyString($order['shipping_address']['zip']);
            $data['shipping_city'] = $this->notSetToEmptyString($order['shipping_address']['city']);
            $data['shipping_country'] = $this->notSetToEmptyString($order['shipping_address']['country']);
            $data['shipping_phone'] = $this->notSetToEmptyString($order['shipping_address']['phone']);
//          $data['shipping_tax'] = $this->notSetToEmptyString($order['shipping_address']['address1']);

            $data['payment_method'] = $this->notSetToEmptyString($order['gateway']);
//          $data['shipping_method'] = $this->notSetToEmptyString($content['shipping_method']);
            /*new*/
            $data['description'] = $this->notSetToEmptyString($order['note']);
            $data['products'] = array();
            $data['tags'] = array();

            foreach ($order['line_items'] as $id => $product) {
//            debug($product);
                $data['products'][$id]['product_id'] = $product['id'];
                $data['products'][$id]['name'] = $product['title'];
                $data['products'][$id]['qty'] = $product['quantity'];
                $data['products'][$id]['price'] = $product['price'];
                $data['products'][$id]['discount'] = $product['total_discount'];
                /*new*/
                $data['products'][$id]['sku'] = $this->notSetToEmptyString($product['sku']);
                $data['products'][$id]['description'] = $this->notSetToEmptyString($product['name']);
                $data['products'][$id]['tax'] = $this->notSetToEmptyString($product['tax_lines'][0]['price']);
            }
            try {
                \Cake\Log\Log::debug('Shopify ShopifyOrderConnector call ActivityEcommerceAddUserBean by ' . $data['email'] . ' on ' . $params['shop_url']);

                $this->createCrmConnection($customerId);
                $changeStatusBean = new ActivityEcommerceChangeStatusBean();
                $changeStatusBean->setCustomer($customerId)
                    ->setSource($this->shopUrl)
                    ->setToken($this->shopUrl)
                    ->setDataRaw($data);
                ActionsManager::pushOrder($changeStatusBean);
            } catch (\Exception $e) {
                // Log error
            }
        }
        return true;
    }

    public function Oldread($customerId = null)
    {

        $orders = $this->shopify->Order->get();

        foreach ($orders as $order) {

            $data = [];
            $data['source'] = $this->shopUrl;
            $data['email'] = $this->notSetToEmptyString($order['email']);
            $data['number'] = $this->notSetToEmptyString($order['order_number']);
            $data['orderdate'] = $order['created_at'];
            $data['order_status'] = $this->notSetToEmptyString($order['financial_status']);
            $data['total'] = $this->notSetToEmptyString($order['total_price']);
            $data['currency'] = $this->notSetToEmptyString($order['currency']);
            $data['tax_total'] = $this->notSetToEmptyString($order['total_tax']);
            $data['subtotal'] = $this->notSetToEmptyString($order['subtotal_price']);
            $data['cart_discount'] = $this->notSetToEmptyString($order['total_discounts']);

//          $data['shipping_total'] = $this->notSetToEmptyString($content['shipping_total']);
            $data['shipping_firstname'] = $this->notSetToEmptyString($order['shipping_address']['shipping_firstname']);
            $data['shipping_lastname'] = $this->notSetToEmptyString($order['shipping_address']['last_name']);
            $data['shipping_address'] = $this->notSetToEmptyString($order['shipping_address']['address1']);
            $data['shipping_postalcode'] = $this->notSetToEmptyString($order['shipping_address']['zip']);
            $data['shipping_city'] = $this->notSetToEmptyString($order['shipping_address']['city']);
            $data['shipping_country'] = $this->notSetToEmptyString($order['shipping_address']['country']);
            $data['shipping_phone'] = $this->notSetToEmptyString($order['shipping_address']['phone']);
//          $data['shipping_tax'] = $this->notSetToEmptyString($order['shipping_address']['address1']);

            $data['payment_method'] = $this->notSetToEmptyString($order['gateway']);
//          $data['shipping_method'] = $this->notSetToEmptyString($content['shipping_method']);
            /*new*/
            $data['description'] = $this->notSetToEmptyString($order['note']);
            $data['products'] = array();
            $data['tags'] = array();

            foreach ($order['line_items'] as $id => $product) {
//            debug($product);
                $data['products'][$id]['product_id'] = $product['id'];
                $data['products'][$id]['name'] = $product['title'];
                $data['products'][$id]['qty'] = $product['quantity'];
                $data['products'][$id]['price'] = $product['price'];
                $data['products'][$id]['discount'] = $product['total_discount'];
                /*new*/
                $data['products'][$id]['sku'] = $this->notSetToEmptyString($product['sku']);
                $data['products'][$id]['description'] = $this->notSetToEmptyString($product['name']);
                $data['products'][$id]['tax'] = $this->notSetToEmptyString($product['tax_lines'][0]['price']);
//            $data['products'][$id]['category'] = $this->notSetToEmptyString($product['category']);
                /*new*/
            }

//          foreach ($tags as $id => $tag) {
//            $data['tags']['name'][] = $tag;
//          }

            try {


                $this->createCrmConnection($customerId);

                $changeStatusBean = new ActivityEcommerceChangeStatusBean();
                $changeStatusBean->setCustomer($customerId)
                    ->setSource($this->shopUrl)
                    ->setToken($this->shopUrl)
                    ->setDataRaw($data);

                ActionsManager::pushOrder($changeStatusBean);

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