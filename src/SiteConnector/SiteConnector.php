<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\SiteConnector;

use WR\Connector\Connector;
use WR\Connector\IConnector;

class SiteConnector extends Connector implements IConnector
{

    private $objectId;

    function __construct($params)
    {
        if ($params != null) {
        }

    }

    public function connect($config)
    {
        return "connect";
    }

    public function read($objectId = null)
    {
        if ($this->objectId == null) {
            return [];
        }
        $objectId = $this->objectId;
    }

    /**
     * @return array
     */
    public function write($content)
    {


    }

    public function update($content, $objectId)
    {
        return "Ho scritto " . $content . " su " . $objectId;
    }

    public function delete($objectId = null)
    {
    }

    public function mapFormData($data) {


    }

    public function stats($objectId)
    {

    }

    public function comments($objectId, $operation = 'r', $content = null)
    {

    }


    public function user($objectId)
    {

    }

    public function add_user($content)
    {

    }

    public function update_categories($content)
    {

    }
}