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

class InstagramBusinessPhotoConnector extends FacebookConnector
{

  public function __construct($params) {
    parent::__construct($params);
  }

  /**
   *
   */
  public function write($content)
  {
      return null;
  }

  public function update($content, $objectId)
  {
      return null;
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