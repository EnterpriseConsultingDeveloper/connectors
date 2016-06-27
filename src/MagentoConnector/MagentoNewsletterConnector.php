<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\MagentoConnector;


use Cake\Network\Http\Client;
use Cake\ORM\TableRegistry;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use WR\Connector\CRMManager;

class MagentoNewsletterConnector extends MagentoConnector
{

    public function __construct($params) {
        parent::__construct($params);
    }

    /**
     *
     */
    public function write($content)
    {
        if($this->_magetoken != null) {
            $publishPath = $this->_mageapipath . 'publish';
            $response = $this->_http->post($publishPath, [
                'type' => 'newsletter',
                'content' => $content,
                'content_id' => $content['content']['original_table_id'],
                'token' => $this->_magetoken,
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
    public function add_user($content)
    {
        //It's not correct to implement this here. Trying to find a different solutions
        $nlRecipientLists = TableRegistry::get('MarketingTools.MtNewsletterRecipientLists');
        $listId = null;
        if (isset($content['list_name'])) {
            $listId = $nlRecipientLists->saveFromConnector(
                $content['list_name'], $content['customer_id']);
        }

        $nlRecipients = TableRegistry::get('MarketingTools.MtNewsletterRecipients');
        $nlRecipient = $nlRecipients->newEntity();

        $nlRecipient->customer_id = $this->notSetToEmptyString($content['customer_id']);
        $nlRecipient->name = $this->notSetToEmptyString($content['name']);
        $nlRecipient->surname = $this->notSetToEmptyString($content['surname']);
        $nlRecipient->email = $this->notSetToEmptyString($content['email']);
        $nlRecipient->mobile = $this->notSetToEmptyString($content['mobile']);
        $nlRecipient->newsletter_recipient_list_id = $listId; //Maybe null

        try {
            $res = $nlRecipients->saveFromConnector($nlRecipient);

            if($res) {
                //$cmrRes = $this->pushToCrm($content['customer_id'], $res);
                $crmManager = new CRMManager();
                $cmrRes = $crmManager->pushClientToCrm($content['customer_id'], $res);

                //debug($cmrRes); die;
            }
            return $res;
        } catch (\PDOException $e) {
            return false;
        }
    }

    private function notSetToEmptyString (&$myString) {
        return (!isset($myString)) ? '' : $myString;
    }


    private function pushToCrm ($customerId, $nlRecipient) {
        //Viene richiesto l’inserimento del nuovo contatto associandolo all’id cliente suite n.7
        //http://socialcrm.whiterabbitsuite.com/rest/client/7/
        //TODO sistemare la url
        $service_url = 'http://socialcrm.whiterabbitsuite.com/rest/client/' . $customerId . '/';
        $fName = $nlRecipient->name != null ? $nlRecipient->name : $nlRecipient->email;
        $lName = $nlRecipient->surname != null ? $nlRecipient->surname : $nlRecipient->email;
        $data = array(
            'facebookid' => null,
            'externalid' => $nlRecipient->id,
            'source' => 'suite',
            'companyname' => $customerId,
            'firstname'=> $fName,
            'lastname'=> $lName,
            'vatnumber'=> null,
            'taxcode' => null,
            'address' => null,
            'city' => null,
            'zone' => null,
            'postalcode' => null,
            'province' => null,
            'nation' => null,
            'email1' => $nlRecipient->email,
            'email2' => null,
            'telephone1' => null,
            'telephone2' => null,
            'mobilephone1' => $nlRecipient->mobile,
            'mobilephone2' => null,
            'faxnumber' => null,
            'birthdaydate' => null,
            'birthplace' => null,
            'newsletter' => '1',
            'ordertotal' => '0',
            'orderaverage' => '0'
        );

        $httpClient = new Client();
        $response = $httpClient->post($service_url, $data);

        return $response;
    }

}