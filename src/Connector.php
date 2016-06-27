<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 24/02/2016
 * Time: 15:34
 */

namespace WR\Connector;


abstract class Connector
{
    public $connector;
    public function __construct($connector)
    {
        $this->connector = $connector; // Configuration params
    }

    public function install($config)
    {
        // Manage new connector installation

    }

    public function connect($config)
    {
        return "connect";
    }

    public function read()
    {
        return "Ho letto da FB";
    }

    public function write($params)
    {
        return "Ho scritto " . $params . " da FacebookConnector";
    }

    public function update($content, $objectId)
    {
        return "Ho scritto " . $content . " su " . $objectId;
    }

    public function delete($objectId)
    {
        return "Ho cancellato " . $objectId;
    }

    public function comments($objectId)
    {
        return "Commenti " . $objectId;

    }

    public function user($objectId)
    {
        return "Utente " . $objectId;

    }

    public function add_user($content)
    {
        return "Utente " . $content;

    }

    public function update_categories($content)
    {
        return "update_categories " . $content;

    }
}