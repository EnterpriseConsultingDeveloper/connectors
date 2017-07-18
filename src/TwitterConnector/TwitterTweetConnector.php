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

class TwitterTweetConnector extends TwitterConnector
{

    public function __construct() {
        $params = [];
        parent::__construct($params);
    }

    /**
     *
     */
    public function write($content)
    {

    }

    /**
     * @param null $objectId
     * @return array|mixed
     */
    public function read($objectId = null)
    {
        if ($objectId == null) {
            return [];
        }

        $url = $this->tw . '1.1/statuses/show.json';
        $getfield = '?id=' . $objectId;

        $requestMethod = 'GET';

        $res = $this->twitter->setGetfield($getfield)
            ->buildOauth($url, $requestMethod)
            ->performRequest();

        return $res;
    }


    public function update($content, $objectId)
    {


    }

}