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
        // \Cake\Log\Log::debug('Prestashop write $content call: ' . print_r($content, true));


        $products = (unserialize($content['productActivity']));
        $data['source'] = $this->notSetToEmptyString($content['sourceId']);
        $data['email'] = $this->notSetToEmptyString($content['email']);
        $data['orderNumber'] = $this->notSetToEmptyString($content['orderNum']);
        $data['orderDate'] = Time::createFromFormat('Y-m-d H:i:s', $this->notSetToEmptyString($content['orderDate']))->toAtomString();
        $data['orderStatus'] = $this->notSetToEmptyString($content['orderState']);
        $data['orderTotal'] = $this->notSetToEmptyString($content['orderTotal']);
        $data['description'] = $this->notSetToEmptyString($content['orderNote']);
        $data['products'] = array();

        foreach ($products as $id => $product) {
            $data['products'][$id]['productId'] = $product['product_id'];
            $data['products'][$id]['productName'] = $product['name'];
            $data['products'][$id]['productQuantity'] = $product['qty'];
            $data['products'][$id]['productPrice'] = $product['price'];
            $data['products'][$id]['productDiscount'] = $product['discount'];
        }

        //\Cake\Log\Log::debug('Prestashop write $data: ' . print_r($data, true));
        // \Cake\Log\Log::debug('Prestashop write customer_id: ' . print_r($content['customer_id'], true));

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