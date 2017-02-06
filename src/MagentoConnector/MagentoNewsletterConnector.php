<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\MagentoConnector;

use App\Lib\WhiteRabbit\WRClient;
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
        $data['mobilephone1'] = $this->notSetToEmptyString($content['mobile']);

        try {
            //nlRecipients->saveFromConnector($nlRecipient);

            //if($res) {
            //$cmrRes = $this->pushToCrm($content['customer_id'], $res);
            $crmManager = new CRMManager();
            $cmrRes = $crmManager->pushClientToCrm($content['customer_id'], $data);

            //debug($cmrRes); die;
            //}
            return $cmrRes;
        } catch (\PDOException $e) {
            return false;
        }
    }

}