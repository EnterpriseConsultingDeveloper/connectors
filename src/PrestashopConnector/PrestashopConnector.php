<?php
/**
 * Created by Fabio Mugnano.
 * User: user
 * Date: 08/06/2017
 * Time: 15:31
 */

namespace WR\Connector\PrestashopConnector;


use Cake\ORM\TableRegistry;
use http\Message;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use App\Lib\WhiteRabbit\WRClient;
use App\Lib\CRM\CRMManager;
use Cake\Log\Log;


class PrestashopConnector extends Connector implements IConnector
{
    protected $_http;
    protected $_psapipath;
    //protected $_psuser;
    //protected $_pspass;
    protected $_pstoken;
    protected $_pswsauthkey;

    function __construct($params)
    {
        // Call Prestashop app

        $this->_http = new WRClient();
        $this->_psapipath = $params['apipath'];
        $this->_pswsauthkey = $params['ps_ws_auth_key'];
       // $this->_suitetoken = $params['token'];
        //$this->_psuser = $params['username'];
        //$this->_pspass = $params['password'];
        $connectPath = $this->_psapipath . 'connect';
        try {
        $response = $this->_http->post($connectPath, [
                'ps_ws_auth_key' => $this->_pswsauthkey,
        ]);
        $this->_pstoken = json_decode($response->body)->token;
        } catch (\PDOException $e) {
            $this->_pstoken = '';
            \Cake\Log\Log::debug('PrestashopConnector __construct error  : ' . print_r($e, true));
        }


    }

    public function connect($config)
    {
        if ($this->_pstoken != null) {
            $readPath = $this->_psapipath . 'connect';

            $response = $this->_http->get($readPath, [
                'q' => 'categories',
                'token' => $this->_pstoken
            ]);
            $bodyResp = json_decode($response->body(), true);
            if (isset($bodyResp['categories']))
                $wp_category = $bodyResp['categories'];
            if (isset($bodyResp['authors']))
                $wp_authors = $bodyResp['authors'];
        }
    }

    public function read($objectId = null)
    {


    }


    public function readPublicPage($objectId = null)
    {

    }


    public function update($content, $objectId)
    {
    }

    public function delete($objectId = null)
    {
        if ($this->_pstoken != null) {
            $publishPath = $this->_psapipath . 'delete';
            $response = $this->_http->post($publishPath, [
                'type' => 'post',
                'content_id' => $objectId,
                'token' => $this->_pstoken
            ]);
            $bodyResp = json_decode($response->body(), true);
            return $bodyResp['result'];
        } else {
            return false;
        }
    }

    public function mapFormData($data)
    {
        return $data;
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



    public function captureFan($objectId = null)
    {

    }

    public function update_categories($content)
    {

    }


    private function notSetToEmptyString(&$myString)
    {
        return (!isset($myString)) ? '' : $myString;
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

    public function setError($message)
    {

    }

}
