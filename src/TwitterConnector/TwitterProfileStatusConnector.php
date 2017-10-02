<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\TwitterConnector;


use Twitter\Twitter;
use WR\Connector\Connector;
use WR\Connector\IConnector;

class TwitterProfileStatusConnector extends TwitterConnector
{

    public function __construct($params) {
        parent::__construct($params);
    }

    /**
     * function write
     */
    public function write($content)
    {
        $post = strip_tags($content['content']['body']);
        $url = $this->tw . '1.1/statuses/update.json';

        $requestMethod = 'POST';

        $postfields = array(
            'screen_name' => $this->profileId,
            'status' => $post,
            //'in_reply_to_status_id' => '879728813617401856'
        );

        $res = $this->twitter->buildOauth($url, $requestMethod)
            ->setPostfields($postfields)
            ->performRequest();

        return $res;

    }

    public function read($objectId = null)
    {
        if ($objectId == null) {
            return [];
        }

        //$this->fb->setDefaultAccessToken($this->longLivedAccessToken);
        $streamToRead = '/' . $objectId;
        $response = $this->fb->get($streamToRead);
        return($response->getDecodedBody());
    }


    public function update($content, $objectId)
    {
        $post = strip_tags($content['content']['body']);
        if ($content['content']['main_url'] != null) {
            $post .= " " . $content['content']['main_url'];
        }

        $data = [
            'title' => $content['content']['title'],
            'message' => $post,
        ];

        $streamToRead = '/' . $objectId;
        $response = $this->fb->post($streamToRead, $data);

        $nodeId = $response->getGraphNode()->getField('id');
        return $nodeId;

    }

}