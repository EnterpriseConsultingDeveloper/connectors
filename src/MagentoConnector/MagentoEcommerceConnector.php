<?php
/**
 * Created by Dino Fratelli / Mugnano Fabio.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\MagentoConnector;


use WR\Connector\Connector;
use WR\Connector\IConnector;
use Cake\ORM\TableRegistry;
use App\Lib\CRM\CRMManager;

class MagentoEcommerceConnector extends MagentoConnector
{

    public function __construct($params) {
        parent::__construct($params);
    }

    /**
     * @param $content
     * @return bool
     */
    public function write($content)
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
        $data['productActivity'] =  (unserialize($content['productActivity']));
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
    public function add_user($content)
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

        try {
            //actionid =  $data['typeid']  . $data['operation']
            $crmManager = new CRMManager();
            $data['typeid'] = $crmManager::$ecommerceTypeId;
            $data['operation'] = $crmManager::$ecommerceActionAddUserId;
            $crmManager->setCustomer($content['customer_id']);
            $cmrRes = $crmManager->pushClientToCrm($content['customer_id'], $data);

            return $cmrRes;
        } catch (\PDOException $e) {
            return false;
        }
    }

    private function notSetToEmptyString (&$myString) {
        return (!isset($myString)) ? '' : $myString;
    }

}