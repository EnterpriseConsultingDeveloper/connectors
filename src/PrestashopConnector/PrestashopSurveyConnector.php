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

class PrestashopSurveyConnector extends PrestashopConnector
{

    public function __construct($params) {
        parent::__construct($params);
    }

    /**
     *
     */
    public function write($content)
    {
        $publishPath = $this->_mageapipath . 'publish';
        $response = $this->_http->post($publishPath, [
            'type' => 'survey',
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