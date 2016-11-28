<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\RSSConnector;


use Cake\ORM\TableRegistry;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use App\Lib\WhiteRabbit\WRClient;

class RSSConnector extends Connector implements IConnector
{
    protected $_http;

    private $objectId;
    private $mage;

    function __construct($params)
    {
        $this->_http = new WRClient();
    }

    public function connect($config)
    {
        return "connect";
    }

    public function read($objectId = null)
    {
    }

    /**
     * @return array
     */
    public function write($content)
    {
    }

    public function update($content, $objectId)
    {
    }

    public function delete($objectId = null)
    {
    }

    public function mapFormData($data) {
        return $data;
    }

    public function stats($objectId)
    {
    }

    public function comments($objectId)
    {
    }

    public function user($objectId)
    {
    }

    public function add_user($content)
    {
    }

}