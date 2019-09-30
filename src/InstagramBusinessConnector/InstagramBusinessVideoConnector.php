<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\FacebookConnector;


use Facebook\Facebook;
use WR\Connector\Connector;
use WR\Connector\IConnector;

class InstagramBusinessVideoConnector extends FacebookConnector
{

    public function __construct($params) {
        parent::__construct($params);
    }

    /**
     *
     */
    public function write($content)
    {
        $this->fb->setDefaultAccessToken($this->longLivedAccessToken);

        $post = strip_tags($content['content']['body']);
        if ($content['content']['main_url'] != null) {
            $post .= " " . $content['content']['main_url'];
        }

        $data = [
            'title' => $content['content']['title'],
            'message' => $post,
        ];

        if ($content['content']['main_video'] != null) {
            $data['source'] = $this->fb->videoToUpload($content['content']['main_video']);
        }

        $response = $this->fb->post('me/videos', $data);

        $nodeId = $response->getGraphNode()->getField('id');

        $info['id'] = $nodeId;
        $info['url'] = 'http://www.facebook.com/' . $nodeId;

        return $info;

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

        if ($content['content']['main_video'] != null) {
            $data['source'] = $this->fb->videoToUpload($content['content']['main_video']);
        }

        $streamToRead = '/' . $objectId;
        $response = $this->fb->post($streamToRead, $data);

        $nodeId = $response->getGraphNode()->getField('id');
        return $nodeId;

    }

    public function read($objectId = null)
    {
        if ($objectId == null) {
            return [];
        }

        $this->fb->setDefaultAccessToken($this->longLivedAccessToken);
        $streamToRead = '/' . $objectId;
        $response = $this->fb->get($streamToRead);
        return($response->getDecodedBody());
    }

}