<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\WhiterabbitConnector;


use WR\Connector\Connector;
use WR\Connector\IConnector;
use Cake\ORM\TableRegistry;
use App\Lib\CRM\CRMManager;
use App\Lib\ActionsManager\ActionsManager;
use App\Lib\ActionsManager\Activities\ActivityEcommerceAddUserBean;
use App\Lib\ActionsManager\Activities\ActivityEcommerceChangeStatusBean;
use App\Controller\MultiSchemaTrait;
use Cake\I18n\Time;
use App\Controller\Component\UtilitiesComponent;

class WhiterabbitEcommerceConnector extends WhiterabbitConnector
{
    use MultiSchemaTrait;
    public function __construct($params) {
        parent::__construct($params);
    }

    /**
     *
     */
    public function write_old($content)
    {
//        $data = [];
        $data['orderIdExt'] = $this->notSetToEmptyString($content['orderIdExt']);
        $data['sourceId'] = $this->notSetToEmptyString($content['sourceId']);
        $data['orderNum'] = $this->notSetToEmptyString($content['orderNum']);
        $data['orderDate'] = $this->notSetToEmptyString($content['orderDate']);
        $data['orderTotal'] =  $this->notSetToEmptyString($content['orderTotal']);
        $data['email'] =  $this->notSetToEmptyString($content['email']);
        $data['orderState'] =  $this->notSetToEmptyString($content['orderState']);
        $data['orderNote'] =  $this->notSetToEmptyString($content['orderNote']);
        $data['site_name'] = $this->notSetToEmptyString($content['site_name']);
        $data['productActivity'] = unserialize($content['productActivity']);

        $async = isset($content['crm_push_async']) && $content['crm_push_async']==true;

        try {
            $crmManager = new CRMManager();
            $crmManager->setCustomer($content['customer_id']);
            return $crmManager->pushOrderToCrm($content['customer_id'], $data, $async);
        } catch (\PDOException $e) {
            return false;
        }

    }

    public function add_user($contact)
    {
        //\Cake\Log\Log::debug('Wordpress add_user pre $contact: ' . print_r($contact, true));
        $contact['uniqueId'] = $contact['email'];

        if (!empty($contact['province'])) {
            $contact['province'] = UtilitiesComponent::findCriteriaId($contact['province']);
            //$contact['province'] = '20541';
        }

        if (!empty($contact['birthdaydate'])) {
            $contact['birthdaydate'] = Time::createFromFormat('Y-m-d H:i:s', $contact['birthdaydate'])->toAtomString();
        }

        if (!empty($contact['date_add'])) {
            $contact['date'] = $contact['date_add'];
        }

        //\Cake\Log\Log::debug('Whiterabbit add_user post $contact: ' . print_r($contact, true));


        $customerId = $contact['customer_id'];
        if (empty($customerId)) {
            // unauthorized
            return false;
        }

        $this->createCrmConnection($customerId);
        $contactBean = new ActivityEcommerceAddUserBean();

        try {
            $contactBean->setCustomer($customerId)
                ->setSource($contact['site_name'])
                ->setToken($contact['site_name'])// identificatore univoco della fonte del dato
                ->setDataRaw($contact);
            //       \Cake\Log\Log::debug('Prestashop $contactBean : ' . print_r($contactBean, true));
            ActionsManager::pushActivity($contactBean);
        } catch (\Throwable $th) {
            // \Cake\Log\Log::debug('Prestashop contact exception: ' . print_r($th, true));
            return false;
        }
        /*
        */

        return true;
    }

    public function write($content)
    {
        /*$data = array(
            'orderIdExt' => '100',
            'sourceId' => 'Prestashop',
            'orderNum' => '100',
            'orderDate' => '2016-11-30',
            'orderTotal' => '100.10'
        );*/
        $data = [];
        // \Cake\Log\Log::debug('Prestashop write $content call: ' . print_r($content, true));

        $products = (unserialize($content['productActivity']));
        $data['source'] = UtilitiesComponent::setSource($this->notSetToEmptyString($content['source']));
        $data['email'] = $this->notSetToEmptyString($content['email']);
        $data['number'] = $this->notSetToEmptyString($content['number']);
        $data['orderdate'] = Time::createFromFormat('Y-m-d H:i:s', $this->notSetToEmptyString($content['orderdate']))->toAtomString();
        $data['order_status'] = $this->notSetToEmptyString($content['order_status']);
        $data['total'] = $this->notSetToEmptyString($content['total']);

        $data['currency'] = $this->notSetToEmptyString($content['currency']);
        $data['tax_total'] = $this->notSetToEmptyString($content['tax_total']);
        $data['subtotal'] = $this->notSetToEmptyString($content['subtotal']);
        $data['cart_discount'] = $this->notSetToEmptyString($content['cart_discount']);

        $data['shipping_total'] = $this->notSetToEmptyString($content['shipping_total']);
        $data['shipping_firstname'] = $this->notSetToEmptyString($content['shipping_firstname']);
        $data['shipping_lastname'] = $this->notSetToEmptyString($content['shipping_lastname']);
        $data['shipping_address'] = $this->notSetToEmptyString($content['shipping_address']);

        $data['shipping_postalcode'] = $this->notSetToEmptyString($content['shipping_postalcode']);
        $data['shipping_city'] = $this->notSetToEmptyString($content['shipping_city']);
        $data['shipping_country'] = $this->notSetToEmptyString($content['shipping_country']);
        $data['shipping_phone'] = $this->notSetToEmptyString($content['shipping_phone']);

        $data['shipping_tax'] = $this->notSetToEmptyString($content['shipping_tax']);
        $data['payment_method'] = $this->notSetToEmptyString($content['payment_method']);
        $data['shipping_method'] = $this->notSetToEmptyString($content['shipping_method']);
        /*new*/
        $data['description'] = $this->notSetToEmptyString($content['description']);
        $data['products'] = array();

        foreach ($products as $id => $product) {
            $data['products'][$id]['product_id'] = $product['product_id'];
            $data['products'][$id]['name'] = $product['name'];
            $data['products'][$id]['qty'] = $product['qty'];
            $data['products'][$id]['price'] = $product['price'];
            $data['products'][$id]['discount'] = $product['discount'];
            /*new*/
            $data['products'][$id]['sku'] = $this->notSetToEmptyString($product['sku']);
            $data['products'][$id]['description'] = $this->notSetToEmptyString($product['description']);
            $data['products'][$id]['tax'] = $this->notSetToEmptyString($product['tax']);
            $data['products'][$id]['category'] = $this->notSetToEmptyString($product['category']);
            /*new*/
        }

        //\Cake\Log\Log::debug('Whiterabbit write $data: ' . print_r($data, true));
        // \Cake\Log\Log::debug('Whiterabbit write customer_id: ' . print_r($content['customer_id'], true));

        try {
            $changeStatusBean = new ActivityEcommerceChangeStatusBean();
            $this->createCrmConnection($content['customer_id']);
            $changeStatusBean->setCustomer($content['customer_id'])
                ->setSource($data['source'])
                ->setToken($data['source'])// identificatore univoco della fonte del dato
                ->setDataRaw($data);
            ActionsManager::pushOrder($changeStatusBean);


        } catch (\PDOException $e) {
            return false;
        }

        return true;
    }

    public function read($objectId = null)
    {
        if ($objectId == null) {
            return [];
        }

        return $objectId;
    }


    public function update($content, $objectId)
    {
        return $content;
    }


    /**
     * @param $content
     * @return bool|\Cake\Datasource\EntityInterface|mixed
     */
    public function add_user_old($content)
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
        $data['email1'] =  $this->notSetToEmptyString($content['email']);
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
        $data['operation'] = $this->notSetToEmptyString($content['operation']);
        $data['newsletter_subscription_date'] = $this->notSetToEmptyString($content['newsletter_subscription_date']);
        $data['newsletter_subscription_ip'] = $this->notSetToEmptyString($content['newsletter_subscription_ip']);
        $data['typeid'] = $this->notSetToEmptyString($content['typeid']);
        $data['contact_typeid'] = $this->notSetToEmptyString($content['contact_typeid']);
        try {
            //nlRecipients->saveFromConnector($nlRecipient);

            //if($res) {
            //$cmrRes = $this->pushToCrm($content['customer_id'], $res);
            $crmManager = new CRMManager();
            $crmManager->setCustomer($content['customer_id']);
            $data['typeid'] = $crmManager::$ecommerceTypeId;
            $data['operation'] = $crmManager::$ecommerceActionAddUserId;
            $data['actionid'] = $crmManager::$ecommerceActionAddUserId;
            $cmrRes = $crmManager->pushClientToCrm($content['customer_id'], $data);

            //debug($cmrRes); die;
            //}
            return $cmrRes;
        } catch (\PDOException $e) {
            return false;
        }
    }

    private function notSetToEmptyString (&$myString) {
        return (!isset($myString)) ? '' : $myString;
    }

}