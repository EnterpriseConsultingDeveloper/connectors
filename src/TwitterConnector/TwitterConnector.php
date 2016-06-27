<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector;


class TwitterConnector extends Connector implements IConnector
{

    function __construct()
    {
        echo __METHOD__,"\n";
    }

    public function connect($config)
    {
        return "connect";
    }

    public function read($channel, $stream)
    {
        return "Ho letto da " . $channel . " da FacebookConnector";
    }

    public function write($channel, $stream, $data)
    {
        return "Ho scritto " . $data . " da FacebookConnector";
    }

    public function update($channel, $stream, $data)
    {
        return "Ho scritto " . $data . " da FacebookConnector";
    }

    public function delete($channel, $stream, $data)
    {
        return "Ho scritto " . $data . " da FacebookConnector";
    }

    public function mapFormData($data) {
        return $data;
    }
}