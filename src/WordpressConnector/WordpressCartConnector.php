<?php

namespace WR\Connector\WordpressConnector;

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

class WordpressCartConnector extends WordpressConnector
{

    use MultiSchemaTrait;

    function __construct($params)
    {
        parent::__construct($params);
    }

    /**
     * @param $content
     * @return bool
     */
    public function write($content)
    {

        $customerId = $content['customer_id'];
        if ($this->checkCustomerEnabled($customerId) == false) {
            \Cake\Log\Log::debug('Wordpress function write on ' . $content['site_name'] . ' by ' . $content['email'] . ' by customer disabled. customer_id ' . $customerId);
            return false;
        }

        if (empty($content['email'])) {
            \Cake\Log\Log::debug('Wordpress write function on '. $content['site_name']  .' by empty email ' . print_r($content, true));
            return false;
        }

        $products = array();
        if (!empty($content['productActivity'])) {
            $products = unserialize($content['productActivity']);
        }

        \Cake\Log\Log::debug('Wordpress WordpressCartConnector import cart_number ' . $content['id']);
        $data = [];
        $data['source'] = UtilitiesComponent::setSource($this->notSetToEmptyString($content['sourceId']));
        $data['sourceId'] = $data['source'];
        $data['actionId'] = 'changeCart';
        $data['email'] = $this->notSetToEmptyString($content['email']);
        $data['cartNum'] = $this->notSetToEmptyString($content['id']);
        $data['cartIdExt'] = $this->notSetToEmptyString($content['id']);
        $data['cartDate'] = $content['cart_date'];
        $data['cartTotal'] = $this->notSetToEmptyString($content['total_price']);
        $data['currency'] = $this->notSetToEmptyString($content['currency']);
        $data['cartTax'] = $this->notSetToEmptyString($content['total_tax']);
        $data['cartDiscount'] = $this->notSetToEmptyString($content['total_discounts']);
        $data['description'] = $this->notSetToEmptyString($content['note']);
        $data['site_name'] = $data['source'];
        $data['cartClose'] = false;
        $data['products'] = array();

        foreach ($products as $id => $product) {
            $data['products'][$id]['product_id'] = $product['product_id'];
            $data['products'][$id]['name'] = $product['name'];
            $data['products'][$id]['quantity'] = $product['qty'];
            $data['products'][$id]['price'] = $product['price'];
            $data['products'][$id]['category'] = $product['category'];
            $data['products'][$id]['discount'] = $this->notSetToEmptyString($product['discount']);
            $data['products'][$id]['sku'] = $this->notSetToEmptyString($product['sku']);
            $data['products'][$id]['description'] = $this->notSetToEmptyString($product['description']);
            $data['products'][$id]['tax'] = $this->notSetToEmptyString($product['tax']);
        }

        try {
            \Cake\Log\Log::debug('WordpressCartConnector call ActivityEcommerceCartBean by ' . $data['email'] . ' on ' . $data['source']);

            $contentBean = new ActivityEcommerceCartBean();
            $this->createCrmConnection($customerId);
            if(!$this->checkCartExist($content['id'],$data['source'])){
                $data['actionId'] = 'openCart';
            }

            $contentBean->setCustomer($customerId)
                ->setSource($data['source'])
                ->setToken($data['source'])// identificatore univoco della fonte del dato
                ->setDataRaw($data)
                ->setActionId($data['actionId']);
            $contentBean->setTypeIdentities('email');

            $contentBean->setSiteName($data['site_name']);
            $contentBean->setEmail($data['email']);
            $contentBean->setCartdate($data['cartDate']);
            $contentBean->setCurrency($data['currency']);
            $contentBean->setCartDiscount($data['cartDiscount']);
            $contentBean->setTaxTotal($data['cartTax']);
            $contentBean->setDescription($data['description']);
            $contentBean->setNumber($data['cartNum']);
            $contentBean->setTotal($data['cartTotal']);
            $contentBean->setCurrency($data['currency']);
            $contentBean->setProducts($data['products']);
            $contentBean->setClosed($data['cartClose']);
            ActionsManager::pushCart($contentBean);

        } catch (\PDOException $e) {
            return false;
        }

        return true;
    }

    private function notSetToEmptyString(&$myString)
    {
        return (!isset($myString)) ? '' : $myString;
    }

}
