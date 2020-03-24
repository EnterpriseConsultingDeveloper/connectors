<?php

namespace WR\Connector\AdsConnector;

use Cake\I18n\Time;
use Facebook\Facebook;
use Facebook\FacebookRequest;
use WR\Connector\Connector;
use WR\Connector\FacebookConnector\FacebookConnector;
use WR\Connector\IConnector;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Exceptions\FacebookResponseException;
use Cake\Network\Http\Client;
use WR\Connector\ConnectorBean;
use WR\Connector\ConnectorUserBean;
use Cake\Log\Log;
use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

class AdsConnector extends Connector implements IConnector {

    protected $ads;
    protected $connector_type;
    protected $longLivedAccessToken;
    protected $objectAdsId;
    protected $connectorUsersSettingsID;
    var $error = false;

    function __construct($params) {

        switch($params['connector_type']) {
            case "Facebook":
                $this->ads = new FacebookConnector($params);
                $this->connector_type = $params['connector_type'];

                if (@isset($params['connectorUsersSettingsID']))
                    $this->connectorUsersSettingsID = $params['connectorUsersSettingsID'];

                if ($params != null) {
                    if (isset($params['longlivetoken']) && $params['longlivetoken'] != null) {
                        $this->longLivedAccessToken = $params['longlivetoken'];
                    }

                    $this->objectAdsId = isset($params['adaccountid']) ? $params['adaccountid'] : '';
                }

            break;
        }
    }

    /**
     * @return object
     */
    public function getFacebook() {

        try {
            // Returns a `Facebook\FacebookResponse` object
            $object = $this->ads;
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $this->setError($e->getMessage());
            exit;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            $this->setError($e->getMessage());
            exit;
        }

        return $object;
    }

    public function connect($config)
    {
        return "connect";
    }

    public function read($objectId = null)
    {

    }

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

    public function delete($objectId = null)
    {

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

    public function captureFan($objectId = null)
    {
        switch($this->connector_type) {
            case "Facebook":
                    return $this->ads->readLeadGen();
                break;
        }
    }

    public function update_categories($content)
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

    public function mapFormData($data) {
        return $data;
    }

    private function blankForEmpty(&$var) {
        return !empty($var) ? $var : '';
    }

    public function setError($message) {
        return $message;
    }

}
