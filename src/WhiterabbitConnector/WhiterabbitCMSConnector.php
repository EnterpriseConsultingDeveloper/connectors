<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\WhiterabbitConnector;


use WR\Connector\Connector;
use WR\Connector\IConnector;

class WhiterabbitCMSConnector extends WhiterabbitConnector
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
			debug($publishPath); die;
            $response = $this->_http->post($publishPath, [
                'type' => 'post',
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

}