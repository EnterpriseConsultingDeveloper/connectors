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

class FacebookPagePhotoConnector extends FacebookConnector
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

    if ($content['content']['main_image'] == null) {
      $error = ['Error' => 500, 'Message' => 'require image'];
      return $error;
    }

    $post = strip_tags($content['content']['abstract']);
    if ($content['content']['main_url'] != null) {
      $post .= " " . $content['content']['main_url'];
    }

    $data = [
      'title' => $content['content']['title'],
      'message' => $post,
    ];

    if (($content['content']['main_image'] != '')) {
      $data['source'] = $this->fb->fileToUpload($content['content']['main_image']);
    }

    $response = $this->fb->post('me/photos', $data, $this->longLivedAccessToken);

    $nodeId = $response->getGraphNode()->getField('id');

    $info['id'] = $nodeId;
    $info['url'] = 'http://www.facebook.com/' . $nodeId;

    return $info;

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

    if (($content['content']['main_image'] != '')) {
      $data['source'] = $this->fb->fileToUpload($content['content']['main_image']);
    }

    $streamToRead = '/' . $objectId;
    $response = $this->fb->post($streamToRead, $data, $this->longLivedAccessToken);

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