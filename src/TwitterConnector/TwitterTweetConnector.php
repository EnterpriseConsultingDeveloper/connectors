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

        //$json = file_get_contents($this->tw . '1.1/statuses/user_timeline.json?count=10&screen_name=' . $objectId, false, $this->context);
        $data = $this->twitter->get("statuses/show", ["id" => $objectId]);

        if(!isset($data->errors)) {
            $myRow = array();
            $myRow = get_object_vars($data);
            $myRow['user'] = get_object_vars($data->user);

            return $myRow;

        } else {
            return array();
        }
    }


    public function update($content, $objectId)
    {


    }

}