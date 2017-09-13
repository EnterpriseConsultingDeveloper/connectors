<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\InstagramConnector;


use WR\Connector\Connector;
use WR\Connector\IConnector;
use Cake\Network\Http\Client;
use WR\Connector\ConnectorBean;
use WR\Connector\ConnectorUserBean;
use Cake\Log\Log;
use Cake\Collection\Collection;
class InstagramConnector extends Connector implements IConnector
{
    protected $insta;
    protected $accessToken;
    protected $appSecret;

    private $feedLimit;
    private $objectId;
    private $context;
    private $token;

    function __construct($params)
    {
        $config = json_decode(file_get_contents('appdata.cfg', true), true);
        $api_base = $config['api_base'];

        //$this->token = empty($params['accesstoken']) ? '' : $params['accesstoken'];
        $this->insta = $api_base;
    }

    /**
     * @param $config
     * @return string
     */
    public function connect($config)
    {
        return '';
    }



    /**
     * @param null $objectId
     * @return array
     */
    public function read($objectId = null)
    {
        // Read complete public page feed
        if ($objectId == null)
            $objectId = $this->objectId;

        if ($objectId == null)
            return [];

        $objectId = $this->cleanObjectId($objectId);
        $url = $this->insta . 'v1/users/' . $objectId . '/media/recent/?access_token=' . $this->token;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $jsonDecoded = json_decode($response, true); // Returns an array
        return($jsonDecoded);
    }

    /**
     * @param null $objectId
     * @return array
     */
    public function readPublicPage($objectId = null)
    {
        // Read complete public page feed
        if ($objectId == null)
            $objectId = $this->objectId;

        if ($objectId == null)
            return [];

        $objectId = $this->cleanObjectId($objectId);
        //$url = $this->insta . 'v1/users/' . $objectId . '/media/recent/?access_token=' . $this->token;

        //https://api.instagram.com/v1/tags/dino/media/recent?access_token=3561573774.cab61f6.3475cbbc097c4ab1b1dcc4deb69aace6
        $url = $this->insta . 'v1/tags/' . $objectId . '/media/recent/?access_token=' . $this->token;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $jsonDecoded = json_decode($response, true); // Returns an array

        $formattedResult = $this->format_result($jsonDecoded);
        return($formattedResult);
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

    /**
     * @param null $objectId
     */
    public function delete($objectId = null)
    {
        //$response = $this->fb->delete($objectId);
        return null;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function mapFormData($data) {
        return $data;
    }

    public function stats($objectId)
    {
       return null;
    }

    public function comments($objectId, $operation = 'r', $content = null)
    {
        return null;
    }

    public function commentFromDate($objectId, $fromDate) {
        return null;
    }


    public function user($objectId)
    {
        return null;
    }

    public function add_user($content)
    {
        return null;
    }

    public function update_categories($content)
    {
        return null;
    }

    public function captureFan($objectId = null)
    {
        return null;
    }

    /**
     * Object ID can be only screen_name or url like 'dinofratelli' or 'https://www.instagram.com/applenws'
     * @param $objectId
     * @return mixed
     */
    private function cleanObjectId($objectId) {
        if(substr($objectId, 0, 25) === 'https://www.instagram.com/')
            return substr($objectId, 25, strlen($objectId));

        return $objectId;
    }

    /**
     * @return bool
     */
    public function isLogged()
    {

    }

    public function callback($params)
    {
        //$data['code'] = $_GET['code'];
        $code = $params['code'];

        $url = 'https://api.instagram.com/oauth/access_token';
        $opts = "client_id=cab61f6a3ba04ca484c5eb5cc1b8d62a&redirect_uri=" . SUITE_SOCIAL_LOGIN_CALLBACK_URL . "&client_secret=8e89d198b5874d94ac41bb4b588aafb5&grant_type=authorization_code&code=" . $code;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);
        $jsonDecoded = json_decode($response, true); // Returns an array

        $data = [];
        $data['token'] = $jsonDecoded['access_token'];
        $data['username'] = $jsonDecoded['user']['username'];
        $data['profile_picture'] = $jsonDecoded['user']['profile_picture'];
        $data['userid'] = $jsonDecoded['user']['id'];
        $data['code'] = $params['code'];

        return $data;
    }


    /**
     * @param $posts
     * @return array
     */
    private function format_result($posts) {
        $beans = array();
        foreach($posts['data'] as $post) {
            $element =  new ConnectorBean();
            $element->setBody($post['caption']['text']);

            $element->setIsContentMeaningful(1);
            $element->setCreationDate($post['created_time']);
            $element->setMessageId($post['id']);
            $element->setAuthor('');
            $element->setUri($post['link']);

            $element->setRawPost($post);

            $beans[] = $element;
        }
        return $beans;
    }

}