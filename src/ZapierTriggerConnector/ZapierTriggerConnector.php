<?php
/**
 * Created by Fabio Mugnano.
 * User: user
 * Date: 27/03/2019
 * Time: 09:58
 */

namespace WR\Connector\ZapierTriggerConnector;

use WR\Connector\Connector;
use WR\Connector\ConnectorBean;
use WR\Connector\IConnector;
use Cake\I18n\Time;
use Cake\Routing\Router;


class ZapierTriggerConnector extends Connector implements IConnector
{



    function __construct($params)
    {


    }

    public function connect($params)
    {

    }


    public function getToken($code, $params)
    {


    }


    /**
     * @param null $objectId
     * @return array
     */
    public function read($objectId = null)
    {

    }

    /**
     * @param null $objectId
     * @return array
     */
    public function readPublicPage($objectId = null)
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

    /**
     * @param null $objectId
     * @return \Shopify\ShopifyResponse
     */
    public function delete($objectId = null)
    {
    }

    /**
     * @param $data
     * @return mixed
     */
    public function mapFormData($data)
    {
        return $data;
    }

    public function stats($objectId)
    {

    }

    /**
     * @param $objectId
     * @param string $operation
     * @param null $content
     * @return array|mixed|string
     */
    public function comments($objectId, $operation = 'r', $content = null)
    {
    }

    public function commentFromDate($objectId, $fromDate)
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

    public function captureFan($objectId = null)
    {

    }


    /**
     * @return bool
     */
    public function isLogged()
    {

    }

    public function callback($params)
    {
    }

    public function configData()
    {
        return json_decode(file_get_contents('appdata.cfg', true), true);
    }

    public function setError($message)
    {

    }

}
