<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\WordpressConnector;


use WR\Connector\Connector;
use WR\Connector\IConnector;

class WordpressEcommerceConnector extends WordpressConnector
{

    public function __construct($params) {
        parent::__construct($params);
    }

    /**
     *
     */
    public function write($content)
    {
        if($this->_wptoken != null) {
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

}