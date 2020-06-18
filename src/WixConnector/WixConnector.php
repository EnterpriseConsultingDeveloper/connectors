<?php
/**
 * Created by Fabio Mugnano.
 * User: user
 * Date: 21/05/2020
 * Time: 15:31
 */

namespace WR\Connector\WixConnector;

use App\Lib\WhiteRabbit\WRClient;
use WR\Connector\Connector;
use WR\Connector\ConnectorBean;
use WR\Connector\ConnectorManager;
use WR\Connector\IConnector;
use Cake\Network\Http\Client;
use WR\Connector\WixConnection;
use Cake\Collection\Collection;
use DateTimeZone;
use Date;
use DateTime;

class WixConnector extends Connector implements IConnector
{

    protected $client_id;
    protected $client_secret;
    protected $ap_id;
    protected $login_call;
    protected $signup_call;

    function __construct($params)
    {
        $config = json_decode(file_get_contents('appdata.cfg', true), true);
        $config = array(
            'ShopUrl' => isset($params['shop_url']) ? $params['shop_url'] : null,
            'AccessToken' => isset($params['access_token']) ? $params['access_token'] : null,
            'RefreshToken' => isset($params['refresh_token']) ? $params['refresh_token'] : null,
        );
        $this->limitCustomerCall = 30;
        $this->limitOrderCall = 100;
        $this->shopUrl = isset($params['shop_url']) ? $params['shop_url'] : null;

    }

    public function connect($config)
    {
        return "connect";
    }


    /**
     * @param null $objectId
     * @return array
     */
    public function read($objectId = null, $params = null)
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
     * @return \Wix\WixResponse
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


    public function getCountOrders($access_token, $date)
    {
        $configData = $this->configData();

        //$dateCreated['dateCreated']['$gt'] = $date;
        $dateCreated['dateCreated']['$gt'] = "2020-05-10T08:45:21.797Z";

        $params['query'] = array('count' => array(
            'filter' => json_encode($dateCreated)),
        );
        $headers =
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'authorization' => $access_token
                ]
            ];

        $http = new WRClient();
        $response = $http->post($configData['wix_orders_query'], json_encode($params),
            $headers
        );
        debug(json_decode($response->body));
        die;
    }



    public function wixRefreshToken($refresh_token)
    {
        $configData = $this->configData();
        $params = array(
            'grant_type' => $configData['grant_type_refresh_token'],
            'refresh_token' => $refresh_token,
            'client_id' => $configData['client_id'],
            'client_secret' => $configData['client_secret']
        );
        try{
            $http = new WRClient();
            $response = $http->post($configData['wix_oauth_access'], json_encode($params),
                ['type' => 'json']
            );
            $res = json_decode($response->body);
            return ($res);
        } catch (\Exception $e) {
            \Cake\Log\Log::error('Wix WixConnector wixRefreshToken for ' . $this->shopUrl . ' error ' . $e->getMessage());

            return null;
            // Log error
        }



    }

}
