<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:34
 */

/*
 * Edit by Fabio Mugnano.
 * User: user
 * Date: 21/01/2019
 * Time: 10:15
 * */

namespace WR\Connector;

use Cake\I18n\Time;
use App\Lib\ActionsManager\Activities\ActivityTicketActionBean;
use App\Lib\ActionsManager\ActionsManager;

abstract class Connector
{
    public $connector;

    public function __construct($connector)
    {
        $this->connector = $connector; // Configuration params
    }

    public function install($config)
    {
        // Manage new connector installation

    }

    public function connect($config)
    {
        return "connect";
    }

    public function read()
    {
        return "Ho letto da FB";
    }

    public function write($params)
    {
        return "Ho scritto " . $params . " da FacebookConnector";
    }

    public function update($content, $objectId)
    {
        return "Ho scritto " . $content . " su " . $objectId;
    }

    public function delete($objectId)
    {
        return "Ho cancellato " . $objectId;
    }

    public function comments($objectId)
    {
        return "Commenti " . $objectId;

    }

    public function user($objectId)
    {
        return "Utente " . $objectId;

    }

    public function add_user($content)
    {
        return "Utente " . $content;

    }

    public function update_categories($content)
    {
        return "update_categories " . $content;

    }

    public function callback($params)
    {
        return "Callback " . $params . " da FacebookConnector";
    }


    function createTicket($contact, $customerId)
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
            'classification' => 'Assistance',
            'contact_ticket' => $contactsTable->getContactsIDFromEmail($contact['email']),
            'title' => $contact['title'],
            'note' => $contact['message'] . "<br><br>" . $formHtml
        ];

        //\Cake\Log\Log::debug('Whiterabbit createTicket $data: ' . print_r($data, true));


        $result = $ticketsTable->saveTicket($customerId, $data, $contact_id);


        // \Cake\Log\Log::debug('Whiterabbit createTicket $result: ' . print_r($data, true));

        $dataAction = [
            'email1' => $contact['email'],
        ];


        $data = array_merge($data, $dataAction);

        $aTicket = new ActivityTicketActionBean();
        $aTicket->setCustomer($customerId)
            ->setSource($result['data']['ticket_id'])
            ->setDataRaw($data)
            ->setActionId('assign');

        $res = ActionsManager::pushActivity($aTicket);

        return $res;
    }

    public function ceckCustomerEnabled($customer_id)
    {
        $customersTable = \Cake\ORM\TableRegistry::get('Customers');
        $customer = $customersTable->find()
            ->where(['Customers.id' => $customer_id])
            ->first();

        if (!$customer)
            return false;

        return $customer['enabled'];

    }

    public function convertInputHtml($data)
    {
        $html = null;

        foreach ($data as $id => $value) {
            if ($id == "customer_id" || $id == "connector_instance_channel_id" || $id == "uniqueId" || $id == "operation" || $id == "properties" ) {
                continue;
            }
            $html .= "<b>" . $id . "</b>: " . $value;
            $html .= "<br>";
        }

        return $html;
    }


}