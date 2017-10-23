<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\ShopifyConnector;

use WR\Connector\Connector;
use WR\Connector\ConnectorBean;
use WR\Connector\IConnector;
use Cake\Network\Http\Client;
use WR\Connector\ShopifyConnection;
use Cake\Collection\Collection;
use Abraham\ShopifyOAuth\ShopifyOAuth;
use PHPShopify;

class ShopifyOrderConnector extends ShopifyConnector
{

    function __construct($params)
    {
        parent::__construct($params);
    }


    /**
     * @param null $objectId
     * @return array
     */
    public function read($objectId = null)
    {

    }

}