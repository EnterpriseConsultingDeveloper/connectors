<?php
/**
 * Created by Fabio Mugnano.
 * User: user
 * Date: 21/05/2020
 * Time: 15:31
 */

namespace WR\Connector\WixConnector;

use App\Controller\MultiSchemaTrait;
use App\Lib\ActionsManager\ActionsManager;
use App\Lib\ActionsManager\Activities\ActivityEcommerceAddUserBean;
use App\Lib\WhiteRabbit\WRClient;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use Cake\ORM\TableRegistry;
use App\Lib\CRM\CRMManager;

class WixCustomerConnector extends WixConnector
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
     * @add  20/05/2020  Fabio Mugnano <mugnano@enterprise-consulting.it>
     * @copyright (c) 2020, WhiteRabbit srl
     */
    public function read($customerId = null, $params = null)
    {
        $wix_token = $this->wixRefreshToken($params['refresh_token']);
        if (empty($wix_token)) {
            \Cake\Log\Log::error('Wix WixCustomerConnector error for ' . $this->shopUrl . ' wixRefreshToken failed ');
            return false;
        }
        $hasMore = true;
        $customers = array();
        $offset = 0;
        $count_customer_db = 0;
        while ($hasMore) {
            \Cake\Log\Log::debug('Wix WixCustomerConnector call read on ' . $this->shopUrl . ' $offset ' . $offset . " and updatedAt >" . $params['wixapi_lastdate_call']);
            $customers_db = $this->getCustomers($wix_token->access_token, $this->limitCustomerCall, $offset);
            if (empty($customers_db->contacts)) {
                $hasMore = false;
                break;
            }
            $count_customer_db += $customers_db->metadata->items;
            \Cake\Log\Log::debug('Wix WixCustomerConnector call read on ' . $this->shopUrl . ' . $count_customer_db ' . $count_customer_db . " and updatedAt >" . $params['wixapi_lastdate_call']);
            foreach ($customers_db->contacts as $contact) {
                if ($contact->metadata->updatedAt >= $params['wixapi_lastdate_call']) {
                    $customers[] = $contact;
                } else {
                    $hasMore = false;
                    continue;
                }
            }
            if (!$hasMore) {
                continue;
            }
            $hasMore = $customers_db->metadata->hasMore;
            $offset++;
        }
        \Cake\Log\Log::debug('Wix WixCustomerConnector START INSERT for ' . $this->shopUrl . ' Customer num ' . count($customers) . " and updatedAt >" . $params['wixapi_lastdate_call']);

        foreach ($customers as $customer) {
            $data = [];
            $date_createdAt = date('Y-m-d H:i:s', strtotime($customer->metadata->createdAt));
            $data['date'] = $date_createdAt;
            $data['externalid'] = $this->notSetToEmptyString($customer->id);
            $data['name'] = $this->notSetToEmptyString($customer->firstName);
            $data['surname'] = $this->notSetToEmptyString($customer->lastName);
            $data['email'] = $this->notSetToEmptyString($customer->emails[0]->email);
            //$data['note'] = $this->notSetToEmptyString($customer['note']);
            $data['site_name'] = $this->notSetToEmptyString($this->shopUrl);
            $data['telephone1'] = $this->notSetToEmptyString($customer->phones[0]->phone);
            //$data['tags']['name'] = explode(',', $customer['tags']);
            $data['address'] = $this->notSetToEmptyString($customer->addresses[0]->street);
            $data['city'] = $this->notSetToEmptyString($customer->addresses[0]->city);
            $data['nation'] = $this->notSetToEmptyString($customer->addresses[0]->countryCode);
            $data['postalcode'] = $this->notSetToEmptyString($customer->addresses[0]->postalCode);
            //  $data['province'] = $this->notSetToEmptyString($customer['addresses'][0]['province_code']);
            $data['gdpr']['gdpr_marketing']['date'] = Time::createFromFormat('Y-m-d H:i:s', $date_createdAt)->toAtomString();
            $data['gdpr']['gdpr_marketing']['value'] = true;
            \Cake\Log\Log::debug('Wix WixCustomerConnector call ActivityEcommerceAddUserBean for customer ' . $data['email'] . ' into ' . $this->shopUrl);

            try {
                $this->createCrmConnection($customerId);
                $contactBean = new ActivityEcommerceAddUserBean();
                $contactBean->setCustomer($customerId)
                    ->setSource($this->shopUrl)
                    ->setToken($this->shopUrl)
                    ->setDataRaw($data);

                ActionsManager::pushActivity($contactBean);

            } catch (\Exception $e) {
                return false;
            }

        }
        \Cake\Log\Log::debug('Wix WixCustomerConnector END INSERT for ' . $this->shopUrl . ' Customer num ' . count($customers) . " and updatedAt >" . $params['wixapi_lastdate_call']);

        return true;
    }


    /**
     * @param null $objectId
     *
     * @return array
     */


    private function notSetToEmptyString(&$myString)
    {
        return (!isset($myString)) ? '' : $myString;
    }


    public function getCustomers($access_token, $limit, $offset)
    {
        try {
            $configData = $this->configData();
            $params['sort.fieldName'] = "metadata.updatedAt";
            $params['sort.order'] = "DESC";
            if ($offset != 0) {
                $params['paging.limit'] = $limit;
                $params['paging.offset'] = $limit * $offset;
            }
            //$params ="sort.fieldName=lastName&sort.order=ASC";
            $headers =
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'authorization' => $access_token
                    ]
                ];

            $http = new WRClient();
            $response = $http->get($configData['wix_contact_query'], $params,
                $headers
            );

            return json_decode($response->body);

        } catch (\Exception $e) {
            \Cake\Log\Log::error('Wix WixCustomerConnector for ' . $this->shopUrl . ' error ' . $e->getMessage());
            return null;
            // Log error
        }


    }


}