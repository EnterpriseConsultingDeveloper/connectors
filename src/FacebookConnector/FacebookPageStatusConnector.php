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

class FacebookPageStatusConnector extends FacebookConnector
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

        $post = strip_tags($content['content']['abstract']);
        /*
        if ($content['content']['link'] != null) {
          $post .= " " . $content['content']['link'];
        }*/

        $data = [
            'message' => $post,
            'link' => $content['content']['link']
        ];


        if (empty($post) && empty($content['content']['main_image'])) {
            $info['Error'] = true;
            $info['Message'] = print_r('Empty Social Text and Empty Social Image', true);
            return $info;
        }

        if (($content['content']['main_image'] != '')) {
            $data['source'] = $content['content']['main_image'];
        }

        $streamToPost = '/' . $this->objectFbId . '/feed';

        try {
            $response = $this->fb->post($streamToPost, $data, $this->longLivedAccessToken);
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $info['Error'] = true;
            $info['Message'] = print_r($e->getMessage(), true);
            return $info;
        }

        $nodeId = $response->getGraphNode()->getField('id');

        $info['id'] = $nodeId;
        $info['url'] = 'http://www.facebook.com/' . $nodeId;

        return $info;
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


  public function update($content, $objectId)
  {
    $post = strip_tags($content['content']['abstract']);
    if ($content['content']['main_url'] != null) {
      $post .= " " . $content['content']['main_url'];
    }

    $data = [
      'title' => $content['content']['title'],
      'message' => $post,
    ];

    $streamToRead = '/' . $objectId;
    $response = $this->fb->post($streamToRead, $data, $this->longLivedAccessToken);

    $nodeId = $response->getGraphNode()->getField('id');
    return $nodeId;

  }

}
