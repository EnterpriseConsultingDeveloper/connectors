<?php

namespace WR\Connector\AdsConnector;


use Facebook\Facebook;
use WR\Connector\Connector;
use WR\Connector\IConnector;

class FacebookAdsConnector extends AdsConnector
{

    public function __construct($params)
    {
        parent::__construct($params);
    }

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
        return ($response->getDecodedBody());
    }

}