<?php
/**
 * Created by Fabio Mugnano.
 * User: user
 * Date: 21/05/2020
 * Time: 15:31
 */

namespace WR\Connector\WixConnector;

use App\Controller\Component\UtilitiesComponent;
use App\Controller\MultiSchemaTrait;
use App\Lib\ActionsManager\ActionsManager;
use App\Lib\ActionsManager\Activities\ActivityEcommerceAddUserBean;
use App\Lib\ActionsManager\Activities\ActivityEcommerceChangeStatusBean;
use App\Lib\WhiteRabbit\WRClient;
use Cake\I18n\Time;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use Cake\ORM\TableRegistry;
use App\Lib\CRM\CRMManager;

class WixOrderConnector extends WixConnector
{
    use MultiSchemaTrait;

    function __construct($params)
    {
        parent::__construct($params);
    }

    /**
     * @param $customerId
     * @param $params
     * @return bool
     * @add  28/05/2020  Fabio Mugnano <mugnano@enterprise-consulting.it>
     * @copyright (c) 2020, WhiteRabbit srl
     */
    public function read($customerId = null, $params = null)
    {
        $wix_token = $this->wixRefreshToken($params['refresh_token']);
        if (empty($wix_token)) {
            \Cake\Log\Log::error('Wix WixOrderConnector error for ' . $this->shopUrl . ' wixRefreshToken failed ');
            return false;
        }

        $hasMore = true;
        $orders = array();
        $offset = 0;
        $count_order_db = 0;
        while ($hasMore) {
            \Cake\Log\Log::debug('Wix WixOrderConnector call read on ' . $this->shopUrl . ' $offset ' . $offset . " and updatedAt >" . $params['wixapi_lastdate_call']);
            $orders_db = $this->getOrders($wix_token->access_token, $params['wixapi_lastdate_call'], $this->limitOrderCall, $offset);
            if (empty($orders_db->orders)) {
                $hasMore = false;
                break;
            }
            if (count($orders_db->orders) == 0) {
                $hasMore = false;
                break;
            }
            $count_order_db += count($orders_db->orders);
            \Cake\Log\Log::debug('Wix WixOrderConnector call read on ' . $this->shopUrl . ' . $count_customer_db ' . $count_order_db . " and updatedAt >" . $params['wixapi_lastdate_call']);
            $orders = array_merge($orders, $orders_db->orders);
            $offset++;
        }
        \Cake\Log\Log::debug('Wix WixOrderConnector START INSERT for ' . $this->shopUrl . ' Order num ' . count($orders) . " and updatedAt >" . $params['wixapi_lastdate_call']);

        foreach ($orders as $order) {
            \Cake\Log\Log::debug('Wix WixOrderConnector on ' . $this->shopUrl . ' import order_number ' . $order->number);
            $data = [];
            $data['source'] = $this->shopUrl;
            $data['email'] = $this->notSetToEmptyString($order->buyerInfo->email);
            $data['number'] = $this->notSetToEmptyString($order->number);
            //$data['orderdate'] = date(\DateTime::ATOM, strtotime($order->dateCreated));
            $data['orderdate'] = date(\DateTime::ATOM, strtotime($order->lastUpdated));
            $data['order_status'] = $this->notSetToEmptyString($order->fulfillmentStatus);
            $data['total'] = $this->notSetToEmptyString($order->totals->total);
            $data['currency'] = $this->notSetToEmptyString($order->currency);
            $data['tax_total'] = $this->notSetToEmptyString($order->tax);
            $data['subtotal'] = $this->notSetToEmptyString($order->subtotal);
            $data['cart_discount'] = $this->notSetToEmptyString($order->discount->value);

//          $data['shipping_total'] = $this->notSetToEmptyString($content['shipping_total']);
            $data['shipping_firstname'] = $this->notSetToEmptyString($order->shippingInfo->shipmentDetails->address->fullName->firstName);
            $data['shipping_lastname'] = $this->notSetToEmptyString($order->shippingInfo->shipmentDetails->address->fullName->lastName);
            $data['shipping_address'] = $this->notSetToEmptyString($order->shippingInfo->shipmentDetails->address->addressLine1);
            $data['shipping_postalcode'] = $this->notSetToEmptyString($order->shippingInfo->shipmentDetails->address->zipCode);
            $data['shipping_city'] = $this->notSetToEmptyString($order->shippingInfo->shipmentDetails->address->city);
            $data['shipping_country'] = $this->notSetToEmptyString($order->shippingInfo->shipmentDetails->address->country);
            $data['shipping_phone'] = $this->notSetToEmptyString($order->shippingInfo->shipmentDetails->address->phone);
//          $data['shipping_tax'] = $this->notSetToEmptyString($order['shipping_address']['address1']);

            $data['payment_method'] = $this->notSetToEmptyString($order->billingInfo->paymentMethod);
            //   $data['shipping_method'] = $this->notSetToEmptyString($content['shipping_method']);
            /*new*/

            $data['products'] = array();
            $data['tags'] = array();

            foreach ($order->lineItems as $id => $product) {
                $data['products'][$id]['product_id'] = $product->productId;
                $data['products'][$id]['name'] = $product->name;
                $data['products'][$id]['qty'] = $product->quantity;
                $data['products'][$id]['price'] = $product->price;
                $data['products'][$id]['discount'] = $product->discount;
                /*new*/
                $data['products'][$id]['sku'] = $this->notSetToEmptyString($product->sku);
                //  $data['products'][$id]['description'] = $this->notSetToEmptyString($product->discount);
                $data['products'][$id]['tax'] = $this->notSetToEmptyString($product->tax);
            }
            \Cake\Log\Log::debug('Wix WixOrderConnector ActivityEcommerceChangeStatusBean for customer ' . $data['email'] . ' source' . $this->shopUrl);

            try {
                $this->createCrmConnection($customerId);
                $changeStatusBean = new ActivityEcommerceChangeStatusBean();
                $changeStatusBean->setCustomer($customerId)
                    ->setSource($this->shopUrl)
                    ->setToken($this->shopUrl)
                    ->setDataRaw($data);
                ActionsManager::pushOrder($changeStatusBean);
            } catch (\Exception $e) {
                return false;
            }
        }
        \Cake\Log\Log::debug('Wix WixOrderConnector END INSERT for ' . $this->shopUrl . ' Order num ' . count($orders) . " and updatedAt >" . $params['wixapi_lastdate_call']);

        return true;
    }


    private function notSetToEmptyString(&$myString)
    {
        return (!isset($myString)) ? '' : $myString;
    }

    public function getOrders($access_token, $date, $limit, $offset)
    {
        try{
            $configData = $this->configData();
            $dateCreated['lastUpdated']['$gt'] = $date;
            //    $dateCreated['dateCreated']['$gt'] = "2020-05-10T08:45:21.797Z";
            $paging = null;
            if ($offset != 0) {
                $paging['limit'] = $limit;
                $paging['offset'] = $limit * $offset;
            }

            $params["query"] = array(
                'filter' => json_encode($dateCreated),
                'paging' => $paging
            );

            $headers =
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'authorization' => $access_token
                    ]
                ];

            $http = new WRClient();
            $response = $http->post($configData['wix_orders_query'], json_encode($params),
                $headers
            );

            return (json_decode($response->body));

        } catch (\Exception $e) {
            \Cake\Log\Log::error('Wix WixOrderConnector for ' . $this->shopUrl . ' error ' . $e->getMessage());
            return null;
            // Log error
        }
    }


}