<?php
/**
 * Created by Fabio Mugnano.
 * User: user
 * Date: 08/06/2017
 * Time: 15:31
 */

namespace WR\Connector\PrestashopConnector;

use WR\Connector\Connector;
use WR\Connector\IConnector;
use Cake\ORM\TableRegistry;
use App\Lib\CRM\CRMManager;
use App\Lib\ActionsManager\ActionsManager;
use App\Lib\ActionsManager\Activities\ActivityEcommerceAddUserBean;
use App\Lib\ActionsManager\Activities\ActivityEcommerceChangeStatusBean;
use App\Lib\ActionsManager\Activities\ActivityTicketActionBean;
use App\Controller\MultiSchemaTrait;
use Cake\I18n\Time;
use App\Controller\Component\UtilitiesComponent;


class PrestashopContactConnector extends PrestashopConnector
{
    use MultiSchemaTrait;

    public function __construct($params)
    {
        parent::__construct($params);
    }

    /**
     *
     */
    public function write($content)
    {
        if ($this->_wptoken != null) {
            $publishPath = $this->_wpapipath . 'publish';
            $response = $this->_http->post($publishPath, [
                'type' => 'newsletter',
                'content' => $content,
                'content_id' => $content['content']['original_table_id'],
                'token' => $this->_wptoken,
                'datestart' => null,
                'dateend' => null
            ]);
            $bodyResp = json_decode($response->body(), true);
            if ($bodyResp['result'] == true && $bodyResp['error'] == false) {
                $info['id'] = $bodyResp['content_url'];
                $info['url'] = $bodyResp['content_url'];
                return $info;
                //return $bodyResp['content_url']; // Should return the content post reference
            } else {
                return false;
            }

        } else {
            return false;
        }

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
        //$data['companyname'] = $this->notSetToEmptyString($content['customer_id']);
        $data['firstname'] = $this->notSetToEmptyString($content['name']);
        $data['lastname'] = $this->notSetToEmptyString($content['surname']);
        $data['email1'] = $this->notSetToEmptyString($content['email']);
        //  $data['mobilephone1'] = $this->notSetToEmptyString($content['mobile']);
        //  $data['telephone1'] = $this->notSetToEmptyString($content['telephone1']);
        //  $data['operation'] = $this->notSetToEmptyString($content['operation']);
        //  $data['newsletter_subscription_date'] = $this->notSetToEmptyString($content['newsletter_subscription_date']);
        //  $data['newsletter_subscription_ip'] = $this->notSetToEmptyString($content['newsletter_subscription_ip']);
        //  $data['typeid'] = $this->notSetToEmptyString($content['typeid']);
        //  $data['contact_typeid'] = $this->notSetToEmptyString($content['contact_typeid']);
        $data['site_name'] = $this->notSetToEmptyString($content['site_name']);
        $data['id_order_str'] = $this->notSetToEmptyString($content['id_order_str']);
        $data['description'] = $this->notSetToEmptyString($content['message']);
        $data['title'] = $this->notSetToEmptyString($content['title']);

        try {
            $crmManager = new CRMManager();
            $crmManager->setCustomer($content['customer_id']);

            if (!$crmManager->retrieveCustomerClient($content['customer_id'], $content['email'])) {
                $data['typeid'] = $crmManager::$ecommerceTypeId;
                $data['operation'] = $crmManager::$ecommerceActionAddUserId;
                $cmrRes = $crmManager->pushClientToCrm($content['customer_id'], $data);
            }


            $dataAct = array(
                'typeid' => $crmManager::$newslettertLandingPagid,
                'source' => $crmManager::$newslettertLandingPageSource,
                'sourceid' => $content['email'],
                'actionid' => $crmManager::$newslettertLandingPageSubmit,
                //'title' => $crmManager::$ecommerceTypeId . $crmManager::$crmSeparator . $crmManager::$ecommerceActionContactId,
                'note' => $crmManager::$ecommerceTypeId . $crmManager::$crmSeparator . $crmManager::$ecommerceActionContactId . " on " . $data['site_name'] . $data['id_order_str'] . " : " . $data['description'],
                'properties' => serialize($data)
            );

            $crmManager->pushActivityoCrm($content['customer_id'], $dataAct);

            if ($content['ticket'] == 1) {
                $cmrRes = $crmManager->pushSalesTicketToCrm($content['customer_id'], $data, null, false);
            }


            return !empty($cmrRes) ? $cmrRes : true;
        } catch (\PDOException $e) {
            return false;
        }
    }


    public function add_user($contact)
    {
        //\Cake\Log\Log::debug('Prestashop add_user pre $contact: ' . print_r($contact, true));
        $contact['uniqueId'] = $contact['email'];

        \Cake\Log\Log::debug('PrestashopContact add_user post $contact: ' . print_r($contact, true));

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
        if ($contact['ticket'] == 1) {
            //$cmrRes = $crmManager->pushSalesTicketToCrm($content['customer_id'], $data, null, false);
            $this->createTciket($contact, $customerId);
        }

        return true;
    }


    function createTciket($contact, $customerId)
    {
        $contactsTable = \Cake\ORM\TableRegistry::get('Crm.Contacts');
        $usersTable = \Cake\ORM\TableRegistry::get('Users');
        $ticketsTable = \Cake\ORM\TableRegistry::get('Crm.Tickets');

        $time = Time::now();
        $time->setTimezone('Europe/Rome');

        $formHtml = $this->convertInputHtml($contact);
        $user_id = $usersTable->getPaymentUserId($customerId);
        $contact_id = $contactsTable->getContactsIDFormUserID($user_id);

        $data = [
            'start_date' => $time->i18nFormat('dd/MM/yyyy'),
            'contact_sender' => $contact_id,
            'contact_delegate' => $contact_id,
            'status_ticket' => '1',
            'priority' => 'Low',
            'contact_ticket' => $contactsTable->getContactsIDFromEmail($contact['email']),
            'title' => $contact['title'],
            'note' => $contact['message'] . "<br><br>" . $formHtml
        ];
       // \Cake\Log\Log::debug('Prestashop createTciket $data: ' . print_r($data, true));


        $result = $ticketsTable->saveTicket($customerId, $data, $contact_id);

        $dataAction = [
            'email1' => $contact['email'],
        ];

//          debug($result['data']['ticket_id']);

        $data = array_merge($data, $dataAction);

        $aTicket = new ActivityTicketActionBean();
        $aTicket->setCustomer($customerId)
            ->setSource($result['data']['ticket_id'])
            ->setDataRaw($data)
            ->setActionId('assign');

        $res = ActionsManager::pushActivity($aTicket);
    }


    private function notSetToEmptyString(&$myString)
    {
        return (!isset($myString)) ? '' : $myString;
    }


    /**
     * @param $content
     * @param $objectId
     * @return mixed
     */
    public function write_cart($content)
    {
        return $content;
    }


    public function convertInputHtml($data)
    {
        $html = null;

        foreach ($data as $id => $value) {
            if ($id == "customer_id" || $id == "connector_instance_channel_id" || $id == "uniqueId") {
                continue;
            }
            $html .= "<b>" . $id . "</b>: " . $value;
            $html .= "<br>";
        }

        return $html;
    }

}