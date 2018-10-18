<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\WordpressConnector;


use WR\Connector\Connector;
use WR\Connector\IConnector;

class WordpressCMSConnector extends WordpressConnector
{

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
                'type' => 'post',
                'content' => $content,
                'content_id' => $content['content']['original_table_id'],
                'category_id' => $content['content']['category_id'],
                'token' => $this->_wptoken,
                'datestart' => null,
                'dateend' => null
            ], ['timeout' => 120]);
            $body = $this->resultJson($response->body());
            $bodyResp = json_decode($body, true);
            if ($bodyResp['result'] == true && $bodyResp['error'] == false) {
                $info['id'] = $bodyResp['content_id'];
                $info['url'] = $bodyResp['content_url'];
                $info['post_status'] = $bodyResp['post_status'];
                $info['post_date'] = $bodyResp['post_date'];
                $info['debug'] = !empty($bodyResp['debug']) ? $bodyResp['debug'] : null;
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