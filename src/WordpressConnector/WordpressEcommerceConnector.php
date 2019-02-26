<?php
/**
 * Created by Dino Fratelli / Mugnano Fabio.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\WordpressConnector;


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

class WordpressEcommerceConnector extends WordpressConnector
{
    use MultiSchemaTrait;
    public function __construct($params) {
        parent::__construct($params);
    }

    /**
     * @param $content
     * @return bool
     */
    public function write_old($content)
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
        $data['orderTotal'] =  $this->notSetToEmptyString($content['orderTotal']);
        $data['email'] =  $this->notSetToEmptyString($content['email']);
        $data['orderState'] =  $this->notSetToEmptyString($content['orderState']);
        $data['orderNote'] =  $this->notSetToEmptyString($content['orderNote']);
        $data['site_name'] = $this->notSetToEmptyString($content['site_name']);
        $data['productActivity'] = unserialize($content['productActivity']);
        $data['crm_push_async'] = $content['crm_push_async'];
        try {
            $crmManager = new CRMManager();
            $crmManager->setCustomer($content['customer_id']);
            $cmrRes = $crmManager->pushOrderToCrm($content['customer_id'], $data);

            return $cmrRes;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * @param null $objectId
     * @return array|null
     */



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
        // \Cake\Log\Log::debug('Wordpress write $content call: ' . print_r($content, true));
        $shipping = array();
        $products = array();
        if (!empty($content['productActivity'])){
            $products = unserialize($content['productActivity'])  ;
        }

        if (!empty($content['shipping'])){
            $shipping = unserialize($content['shipping']); ;
        }

        $data['source'] = UtilitiesComponent::setSource($this->notSetToEmptyString($content['sourceId']));
        $data['email'] = $this->notSetToEmptyString($content['email']);
        $data['number'] = $this->notSetToEmptyString($content['orderNum']);
        $data['orderdate'] = Time::createFromFormat('Y-m-d H:i:s', $this->notSetToEmptyString($content['orderDate']))->toAtomString();
        $data['order_status'] = $this->notSetToEmptyString($content['orderState']);
        $data['total'] = $this->notSetToEmptyString($content['orderTotal']);
        $data['description'] = $this->notSetToEmptyString($content['orderNote']);

        /*new*/
        $data['currency'] = $this->notSetToEmptyString($content['currency']);
        $data['tax_total'] = $this->notSetToEmptyString($content['tax_total']);
        $data['subtotal'] = $this->notSetToEmptyString($content['subtotal']);
        $data['cart_discount'] = $this->notSetToEmptyString($content['cart_discount']);
        $data['payment_method'] = $this->notSetToEmptyString($content['payment_method']);
        $data['shipping_total'] = $this->notSetToEmptyString($shipping['total']);
        $data['shipping_firstname'] = $this->notSetToEmptyString($shipping['firstname']);
        $data['shipping_lastname'] = $this->notSetToEmptyString($shipping['lastname']);
        $data['shipping_address'] = $this->notSetToEmptyString($shipping['address']);
        $data['shipping_postalcode'] = $this->notSetToEmptyString($shipping['postalcode']);
        $data['shipping_city'] = $this->notSetToEmptyString($shipping['city']);
        $data['shipping_country'] = $this->notSetToEmptyString($shipping['country']);
        $data['shipping_phone'] = $this->notSetToEmptyString($shipping['phone']);
        $data['shipping_tax'] = $this->notSetToEmptyString($shipping['tax']);
        $data['shipping_method'] = $this->notSetToEmptyString($shipping['method']);
        /*new*/

        $data['products'] = array();

        foreach ($products as $id => $product) {
            $data['products'][$id]['product_id'] = $this->notSetToEmptyString($product['product_id']);
            $data['products'][$id]['name'] = $this->notSetToEmptyString($product['name']);
            $data['products'][$id]['qty'] = $this->notSetToEmptyString($product['qty']);
            $data['products'][$id]['price'] = $this->notSetToEmptyString($product['price']);
            $data['products'][$id]['discount'] = $this->notSetToEmptyString($product['discount']);
            /*new*/
            $data['products'][$id]['sku'] = $this->notSetToEmptyString($product['sku']);
            $data['products'][$id]['description'] = $this->notSetToEmptyString($product['description']);
            $data['products'][$id]['tax'] = $this->notSetToEmptyString($product['tax']);
            $data['products'][$id]['category'] = $this->notSetToEmptyString($product['category']);
            /*new*/
        }

        //\Cake\Log\Log::debug('Wordpress write $data: ' . print_r($data, true));

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


    /**
     * @param $content
     * @param $objectId
     * @return mixed
     */
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
        //$data['operation'] = $this->notSetToEmptyString($content['operation']);
        $data['newsletter_subscription_date'] = $this->notSetToEmptyString($content['newsletter_subscription_date']);
        $data['newsletter_subscription_ip'] = $this->notSetToEmptyString($content['newsletter_subscription_ip']);
        $data['typeid'] = $this->notSetToEmptyString($content['typeid']);
        $data['contact_typeid'] = $this->notSetToEmptyString($content['contact_typeid']);
        $data['crm_push_async'] = $content['crm_push_async'];

        try {
            //actionid =  $data['typeid']  . $data['operation']
            $crmManager = new CRMManager();
            $crmManager->setCustomer($content['customer_id']);
            $data['typeid'] = $crmManager::$ecommerceTypeId;
            $data['operation'] = $crmManager::$ecommerceActionAddUserId;
            $data['actionid'] = $crmManager::$ecommerceActionAddUserId;
            $cmrRes = $crmManager->pushClientToCrm($content['customer_id'], $data);

            return $cmrRes;
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
            $contact['birthdaydate'] .= " 00:00:00";
            $contact['birthdaydate'] = Time::createFromFormat('Y-m-d H:i:s', $contact['birthdaydate'])->toAtomString();
        }

        if (!empty($contact['date_add'])) {
            $contact['date'] = $contact['date_add'];
        }

        //\Cake\Log\Log::debug('Prestashop add_user post $contact: ' . print_r($contact, true));

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




    private function notSetToEmptyString (&$myString) {
        return (!isset($myString)) ? '' : $myString;
    }

}