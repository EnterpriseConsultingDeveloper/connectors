<?php

namespace WR\Connector\ShopifyConnector;

use App\Controller\MultiSchemaTrait;
use App\Controller\Component\UtilitiesComponent;
use Cake\Controller\ComponentRegistry;
use App\Lib\ActionsManager\ActionsManager;
use App\Lib\ActionsManager\Activities\ActivityEcommerceCartBean;
use Cake\I18n\Time;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use Cake\ORM\TableRegistry;
use App\Lib\CRM\CRMManager;

class ShopifyCartConnector extends ShopifyConnector
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
     */
    public function read($customerId = null, $params = null)
    {
        $params_call = array();
        if (!empty($params['date'])) {
            $params_call['created_at_min'] = $params['date'];
        }
        $params_call['limit'] = $this->limitCall;
        \Cake\Log\Log::debug('Shopify ShopifyCartConnector call read on ' . $params['shop_url'] . ' params ' .  print_r(json_encode($params_call), true));
        try {
            $count_cart_db = $this->shopify->AbandonedCheckout->count($params_call);
        } catch (\Exception $e) {
            \Cake\Log\Log::debug('Shopify ShopifyCartConnector ERROR call count read on ' . $params['shop_url'] );
            return false;
        }
        \Cake\Log\Log::debug('Shopify ShopifyCartConnector call read on ' . $params['shop_url'] . ' count_cart_db ' . $count_cart_db);

        $cartResource = $this->shopify->AbandonedCheckout();
        $carts = $cartResource->get($params_call);
        $count_cart_crm = count($carts);
        \Cake\Log\Log::debug('Shopify ShopifyCartConnector call carts count ' . $count_cart_crm . ' on ' . $params['shop_url']);

        $nextPageCarts = $cartResource->getNextPageParams();
        $nextPageCartsArray = [];
        while ($nextPageCarts) {
            $nextPageCartsArray = $cartResource->get($cartResource->getNextPageParams());
            $count_cart_crm += count($nextPageCartsArray);
            \Cake\Log\Log::debug('Shopify ShopifyCartConnector call count_cart_crm count ' . $count_cart_crm . ' on ' . $params['shop_url']);
            $carts = array_merge($carts, $nextPageCartsArray);
            $nextPageCarts = $cartResource->getNextPageParams();
        }

        foreach ($carts as $cart) {
            \Cake\Log\Log::debug('Shopify ShopifyCartConnector import cart_number ' . $cart['id']);
            $data = [];
            $data['source'] = $this->shopUrl;
            $data['sourceId'] = $this->shopUrl;
            $data['actionId'] = (!empty($cart['completed_at'])) ? 'closeCart' : ((UtilitiesComponent::checkAbandonedCartTime($customerId, $cart['updated_at'])) ? 'abandonedCart' : (($cart['created_at'] == $cart['updated_at']) ? 'openCart' : 'changeCart'));
            $data['email'] = $this->notSetToEmptyString($cart['email']);
            $data['cartNum'] = $this->notSetToEmptyString($cart['id']);
            $data['cartIdExt'] = $this->notSetToEmptyString($cart['id']);
            $data['cartDate'] = ((!empty($cart['completed_at'])) ? $cart['completed_at'] : $cart['updated_at']);
            $data['cartTotal'] = $this->notSetToEmptyString($cart['total_price']);
            $data['currency'] = $this->notSetToEmptyString($cart['currency']);
            $data['cartTax'] = $this->notSetToEmptyString($cart['total_tax']);
            $data['cartDiscount'] = $this->notSetToEmptyString($cart['total_discounts']);
            $data['description'] = $this->notSetToEmptyString($cart['note']);
            $data['site_name'] = $this->notSetToEmptyString($this->shopUrl);
            $data['products'] = array();

            foreach ($cart['line_items'] as $id => $product) {
                $data['products'][$id]['product_id'] = $product['product_id'];
                $data['products'][$id]['name'] = $product['title'];
                $data['products'][$id]['quantity'] = $product['quantity'];
                $data['products'][$id]['price'] = $product['price'];
                $data['products'][$id]['category'] = '';
                $data['products'][$id]['discount'] = $this->notSetToEmptyString($product['total_discount']);
                $data['products'][$id]['sku'] = $this->notSetToEmptyString($product['sku']);
                $data['products'][$id]['description'] = $this->notSetToEmptyString($product['name']);
                $data['products'][$id]['tax'] = $this->notSetToEmptyString($product['tax_lines'][0]['price']);
            }

            try {
                \Cake\Log\Log::debug('Shopify ShopifyCartConnector call ActivityEcommerceCartBean by ' . $data['email'] . ' on ' . $params['shop_url']);

                $cartBean = new ActivityEcommerceCartBean();
                $this->createCrmConnection($customerId);
                $cartBean->setCustomer($customerId)
                    ->setSource($this->shopUrl)
                    ->setToken($this->shopUrl)// identificatore univoco della fonte del dato
                    ->setDataRaw($data)
                    ->setActionId($data['actionId']);
                $cartBean->setTypeIdentities('email');

                $cartBean->setSiteName($data['site_name']);
                $cartBean->setEmail($data['email']);
                $cartBean->setCartdate($data['cartDate']);
                $cartBean->setCurrency($data['currency']);
                $cartBean->setCartDiscount($data['cartDiscount']);
                $cartBean->setTaxTotal($data['cartTax']);
                $cartBean->setDescription($data['description']);
                $cartBean->setNumber($data['cartNum']);
                $cartBean->setTotal($data['cartTotal']);
                $cartBean->setCurrency($data['currency']);
                $cartBean->setProducts($data['products']);
                ActionsManager::pushCart($cartBean);

            } catch (\PDOException $e) {
                // Log error
            }
        }
        return true;
    }

    private function notSetToEmptyString(&$myString)
    {
        return (!isset($myString)) ? '' : $myString;
    }

}
