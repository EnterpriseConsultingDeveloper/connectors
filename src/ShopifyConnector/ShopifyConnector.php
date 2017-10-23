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

class ShopifyConnector extends Connector implements IConnector
{
    //protected $tw;
    protected $app_key;
    protected $app_secret;

    private $feedLimit;
    private $objectId;
    protected $context;
    protected $profileId;
    protected $twitter;
    protected $api_base;

    protected $access_token;
    protected $access_token_secret;

    function __construct($params)
    {
        /*
        $config = json_decode(file_get_contents('appdata.cfg', true), true);
        $this->api_base = $config['api_base'];
        $this->app_key = $config['api_key'];
        $this->app_secret = $config['api_secret'];

        if(!empty($params['key']) && !empty($params['longlivetoken'])) {
            // ouauth utente
            $this->access_token = $params['key'];
            $this->access_token_secret = $params['longlivetoken'];
        } else {
            // ouauth whiterabbit
            $this->access_token = $config['access_token'];
            $this->access_token_secret = $config['access_token_secret'];
        }



        $this->twitter = new ShopifyOAuth($this->app_key,  $this->app_secret, $this->access_token, $this->access_token_secret);
        */
    }

    public function connect($config)
    {
        return "connect";
    }



    /**
     * @param null $objectId
     * @return array
     */
    public function read($objectId = null)
    {
        // Read complete public page feed, used in STREAM!
        if ($objectId == null) $objectId = $this->objectId;

        if ($objectId == null) {
            return [];
        }

        $objectId = $this->cleanObjectId($objectId);
        if (substr($objectId, 0, 1) === '#') {// hashtag search
            $objectId = str_replace('#', '%23', $objectId);
            //$json = file_get_contents($this->tw . '1.1/search/tweets.json?q=' . $objectId, false, $this->context);
            $dataObj = $this->twitter->get("search/tweets", ["q" => $objectId]);

        } else {
            //$json = file_get_contents($this->tw . '1.1/statuses/user_timeline.json?count=10&screen_name=' . $objectId, false, $this->context);
            $dataObj = $this->twitter->get("statuses/user_timeline", ["count" => 10, "screen_name" => $objectId]);
        }
        //$data = json_decode($json, true);
        // Remove comments that are tweets in_reply_to_status_id
        //debug($data);
        $data = array();
        foreach($dataObj as $myObj) {
            if($myObj->in_reply_to_status_id != '')
                continue;

            $myRow = array();
            $myRow = get_object_vars($myObj);
            $myRow['comments'] = $this->comments($myObj->id, 'r');
            $myRow['user'] = get_object_vars($myObj->user);

            $data[] = $myRow;
        }
        //debug($data); die;
        //in_reply_to_status_id
        // Append users that have taken an action on the page
        $social_users = array();
        $data['social_users'] = $social_users;

        return($data);
    }

    /**
     * @param null $objectId
     * @return array
     */
    public function readPublicPage($objectId = null)
    {
        // Read complete public page feed
        if ($objectId == null) $objectId = $this->objectId;

        if ($objectId == null)
            return [];


        // Will create a public connection
        $bearer_token_creds = base64_encode($this->app_key.':'.$this->app_secret);
        $opts = array(
            'http'=>array(
                'method' => 'POST',
                'header' => 'Authorization: Basic '.$bearer_token_creds."\r\n".
                    'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
                'content' => 'grant_type=client_credentials'
            )
        );

        $context = stream_context_create($opts);
        $json = file_get_contents($this->api_base.'oauth2/token', false, $context);
        $result = json_decode($json, true);

        if (!is_array($result) || !isset($result['token_type']) || !isset($result['access_token']))
            die("Something went wrong. This isn't a valid array: " . $json);

        if ($result['token_type'] !== "bearer")
            die("Invalid token type. Shopify says we need to make sure this is a bearer.");

        $bearer_token = $result['access_token'];
        $opts = array(
            'http'=>array(
                'method' => 'GET',
                'header' => 'Authorization: Bearer ' . $bearer_token
            )
        );
        $context = stream_context_create($opts);
/*
        if(isset($params['profileid']))
            $this->profileId = $params['profileid'];

        $settings = array(
            'oauth_access_token' => $this->access_token,
            'oauth_access_token_secret' => $this->access_token_secret,
            'consumer_key' => $this->app_key,
            'consumer_secret' => $this->app_secret
        );

        $connection = new \ShopifyAPIExchange($settings); */

        $objectId = $this->cleanObjectId($objectId);
        $formatted_res = array();

        try {
            if (substr($objectId, 0, 1) === '#') {// hashtag search
                $objectId = str_replace('#', '%23', $objectId);
                $json = file_get_contents($this->api_base . '1.1/search/tweets.json?q=' . $objectId, false, $context);
            } else {
                $json = file_get_contents($this->api_base . '1.1/statuses/user_timeline.json?count=10&screen_name=' . $objectId, false, $context);
            }

            $res = json_decode($json, true);
            if(isset($res['statuses']))
                $res = $res['statuses'];

            foreach($res as $key => $value) {
                try {
                    // Check if the array returned is not related to content (e.g. stats array)
                    if(!isset($value['text']) || empty($value['text']))
                        continue;

                    if(!isset($value['text']) || empty($value['text']))
                        continue;

                    $element =  new ConnectorBean();
                    $element->setBody($value['text']);
                    $element->setCreationDate($value['created_at']);
                    $element->setMessageId($value['id_str']);
                    $element->setAuthor($value['user']['name']);

                    //https://twitter.com/RadioNightwatch/status/827460856128614401
                    $uri = 'https://twitter.com/' . $objectId . '/status/' . $value['id_str'];
                    $element->setUri($uri);
                    $element->setIsContentMeaningful(1);
                    $element->setRawPost($value);

                    $formatted_res[] = $element;
                } catch (\Exception $e) {
                    continue;
                }
            }
        } catch (\Exception $e) {
            // Do nothing
        }

        return($formatted_res);
    }


    /**
     * @return array
     */
    public function write($content)
    {
        $post = strip_tags($content['content']['body']);

        $res = $this->twitter->post("statuses/update", [
            "status" => $post
        ]);

        return $res;

    }

    public function update($content, $objectId)
    {
        return "Ho scritto " . $content . " su " . $objectId;
    }

    /**
     * @param null $objectId
     * @return \Shopify\ShopifyResponse
     */
    public function delete($objectId = null)
    {
        $response = $this->fb->delete($objectId);
        return $response;
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
        if ($this->objectId == null) {
            return [];
        }

        $stats = [];

        try {
            $statRequest = '/' . $objectId . '/likes';
            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);
            $stats['likes_number'] = count($response->getDecodedBody()['data']);
            $stats['likes'] = $response->getDecodedBody()['data'];
        } catch(ShopifyResponseException $e) {
            $stats['likes'] = [];
            $stats['likes_number'] = 0;
        } catch(ShopifySDKException $e) {
            // When validation fails or other local issues
            echo 'Shopify SDK returned an error: ' . $e->getMessage();
            $stats = [];
        }

        try {
            $statRequest = '/' . $objectId . '/comments';

            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);
            $stats['comment_number'] = count($response->getDecodedBody()['data']);
        } catch(ShopifyResponseException $e) {
            $stats['comment_number'] = 0;
        } catch(ShopifySDKException $e) {
            // When validation fails or other local issues
            echo 'Shopify SDK returned an error: ' . $e->getMessage();
            $stats = [];
        }


        try {
            $statRequest = '/' . $objectId . '/sharedposts';
            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);
            $stats['sharedposts'] = count($response->getDecodedBody()['data']);

        } catch(ShopifyResponseException $e) {
            $stats['sharedposts'] = 0;
        } catch(ShopifySDKException $e) {
            // When validation fails or other local issues
            echo 'Shopify SDK returned an error: ' . $e->getMessage();
            $stats = [];
        }


        try {
            $statRequest = '/' . $objectId . '/insights';
            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);
            $insights = $response->getDecodedBody()['data'];

            $myInsights = [];
            foreach ($insights as $key => $value) {
                $myInsights[$value['name']] = $value['values'][0]['value'];
            }

            $stats['insights'] = $myInsights;

        } catch(ShopifyResponseException $e) {
            $stats['insights'] = [];
        } catch(ShopifySDKException $e) {
            // When validation fails or other local issues
            echo 'Shopify SDK returned an error: ' . $e->getMessage();
            $stats = [];
        }

        return $stats;
    }

    /**
     * @param $objectId
     * @param string $operation
     * @param null $content
     * @return array|mixed|string
     */
    public function comments($objectId, $operation = 'r', $content = null)
    {
        if ($objectId == null) {
            return [];
        }

        if($operation === 'r') {
            // Tweets in response of a tweet are comments
            try {
                $tweetConnector = new ShopifyTweetConnector();
                $originalPost = $tweetConnector->read($objectId);
                $tweeterusername = $originalPost['user']['screen_name'];

                //$json = file_get_contents($this->tw . '1.1/statuses/user_timeline.json?count=10&screen_name=' . $objectId, false, $this->context);
                //$url = $this->tw . '1.1/search/tweets.json';
                //$getfield = '?q=to:' . $tweeterusername . '&since_id=' . $objectId;
                $query = [
                    "q" => "to:" . $tweeterusername,
                    "sinceId" => $objectId
                    //"in_reply_to_status_id"  => $objectId
                ];
                $data = $this->twitter->get("search/tweets", $query);
                //"in_reply_to_status_id" => $objectId
                $resArray = array();
                if(!isset($data->errors)) {
                    foreach($data->statuses as $status) {
                        //debug($status);die;
                        if($status->in_reply_to_status_id != $objectId)
                            continue;

                        $myRow = array();
                        $myRow = get_object_vars($status);
                        $myRow['user'] = get_object_vars($status->user);

                        $resArray[] = $myRow;
                        //unset($resArray[]['search_metadata']);
                    }
                }

                return $resArray;

            } catch(\Exception $e) {
                Log::write('debug', $e);
                return [];
            }
        }

        // In case of write first write comment than read the comment
        if($operation === 'w' && !empty($content)) {
            try {
                $tweetConnector = new ShopifyTweetConnector();
                $originalPost = $tweetConnector->read($objectId);
                $tweeterusername = $originalPost['user']['screen_name'];

                $post = '@' . $tweeterusername . ' ' . strip_tags($content['comment']);
                $res = $this->twitter->post("statuses/update", [
                    "status" => $post,
                    "in_reply_to_status_id" => $objectId
                ]);

                $compatiblePictureArray = [];
                $compatiblePictureArray['data']['is_silhouette'] = false;
                $compatiblePictureArray['data']['url'] = $res->user->profile_image_url_https;

                $compatibleFromArray = [];
                $compatibleFromArray['name'] = $res->user->name;
                $compatibleFromArray['picture'] = $compatiblePictureArray;
                $compatibleFromArray['link'] = '';
                $compatibleFromArray['id'] = $res->user->id;

                $compatibleResArray = [];
                $compatibleResArray['message'] = $res->text;
                $compatibleResArray['created_time'] = $res->created_at;
                $compatibleResArray['like_count'] = $res->favorite_count;
                $compatibleResArray['from'] = $compatibleFromArray;
                $compatibleResArray['id'] = $res->id_str;

                return $compatibleResArray;

            } catch(\Exception $e) {

                return [];
            }
        }
    }

    public function commentFromDate($objectId, $fromDate) {
        if ($this->objectId == null) {
            return [];
        }

        try {
            $statRequest = '/' . $objectId . '/comments';

            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);
            return $response->getDecodedBody()['data'];
        } catch(ShopifyResponseException $e) {
            return [];
        } catch(ShopifySDKException $e) {
            // When validation fails or other local issues
            echo 'Shopify SDK returned an error: ' . $e->getMessage();
            return [];
        }
    }


    public function user($objectId)
    {
        if ($this->objectId == null) {
            return [];
        }

        try {
            $request = $this->fb->request('GET', '/' . $objectId);
            $response = $this->fb->getClient()->sendRequest($request);
            //debug($response->getGraphObject()); die;
            return $response->getDecodedBody();
        } catch(ShopifyResponseException $e) {
            return [];
        } catch(ShopifySDKException $e) {
            // When validation fails or other local issues
            echo 'Shopify SDK returned an error: ' . $e->getMessage();
            return [];
        }
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
     * Object ID can be only screen_name or url like 'dinofratelli' or 'https://twitter.com/applenws'
     * @param $objectId
     * @return mixed
     */
    private function cleanObjectId($objectId) {
        if(substr($objectId, 0, 20) === 'https://twitter.com/')
            return substr($objectId, 20, strlen($objectId));

        if(substr($objectId, 0, 19) === 'http://twitter.com/')
            return substr($objectId, 19, strlen($objectId));

        return $objectId;
    }

    /**
     * @return bool
     */
    public function isLogged()
    {

    }

    public function callback($params) {

        $config = json_decode(file_get_contents('appdata.cfg', true), true);
        $data = array();

        $connection = new ShopifyOAuth($this->app_key,  $this->app_secret, $params['oauth_token'], $params['oauth_token_secret']);
        $access_token = $connection->oauth("oauth/access_token", ["oauth_verifier" => $_REQUEST['oauth_verifier']]);

        // Logged in
        $data['key'] = $access_token['oauth_token'];
        $data['longlivetoken'] = $access_token['oauth_token_secret'];
        $data['screen_name'] = $access_token['screen_name'];
        $data['profileid'] = $access_token['screen_name'];

        return $data;

    }

    public function configData() {
        return json_decode(file_get_contents('appdata.cfg', true), true);
    }

}