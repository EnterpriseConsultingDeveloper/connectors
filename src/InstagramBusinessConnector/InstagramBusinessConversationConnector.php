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

class InstagramBusinessConversationConnector extends FacebookConnector
{

    //TODO: in costruzione
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

    public function read($objectId = null)
    {
        return null;
    }


    public function update($content, $objectId)
    {

        return null;
    }

}