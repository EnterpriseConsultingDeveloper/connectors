<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\TwitterConnector;

use WR\Connector\Connector;
use WR\Connector\ConnectorBean;
use WR\Connector\IConnector;
use Cake\Network\Http\Client;
use WR\Connector\TwitterConnection;
use Cake\Collection\Collection;
class TwitterConnector extends Connector implements IConnector
{
    protected $tw;
    protected $longLivedAccessToken;
    protected $accessToken;
    protected $appSecret;

    private $feedLimit;
    private $objectId;
    private $context;

    function __construct($params)
    {
        $config = json_decode(file_get_contents('appdata.cfg', true), true);
        $api_base = $config['api_base'];

        //This is all you need to configure.
        $app_key = $config['api_key'];
        $app_token = $config['api_secret'];

        $bearer_token_creds = base64_encode($app_key.':'.$app_token);
        //Get a bearer token.
        $opts = array(
            'http'=>array(
                'method' => 'POST',
                'header' => 'Authorization: Basic '.$bearer_token_creds."\r\n".
                    'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
                'content' => 'grant_type=client_credentials'
            )
        );

        $context = stream_context_create($opts);
        $json = file_get_contents($api_base.'oauth2/token', false, $context);
        $result = json_decode($json,true);

        if (!is_array($result) || !isset($result['token_type']) || !isset($result['access_token'])) {
            die("Something went wrong. This isn't a valid array: ".$json);
        }
        if ($result['token_type'] !== "bearer") {
            die("Invalid token type. Twitter says we need to make sure this is a bearer.");
        }
        //Set our bearer token. Now issued, this won't ever* change unless it's invalidated by a call to /oauth2/invalidate_token.
        //*probably - it's not documentated that it'll ever change.
        $bearer_token = $result['access_token'];
        //Try a twitter API request now.
        $opts = array(
            'http'=>array(
                'method' => 'GET',
                'header' => 'Authorization: Bearer '.$bearer_token
            )
        );
        $this->context = stream_context_create($opts);
        $this->tw = $api_base;

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
        // Read complete public page feed
        if ($objectId == null) $objectId = $this->objectId;

        if ($objectId == null) {
            return [];
        }

        $objectId = $this->cleanObjectId($objectId);
        if (substr($objectId, 0, 1) === '#') {// hashtag search
            $objectId = str_replace('#', '%23', $objectId);
            $json = file_get_contents($this->tw . '1.1/search/tweets.json?q=' . $objectId, false, $this->context);
        } else {
            $json = file_get_contents($this->tw . '1.1/statuses/user_timeline.json?count=10&screen_name=' . $objectId, false, $this->context);
        }

        $data = json_decode($json, true);

        // Append users that have taken an action on the page
        $social_users = array();
//        foreach($data as $d) {
//            foreach($d['reactions']['data'] as $social_user) {
//                $ub = new ConnectorUserBean();
//                $ub->setName($social_user['name']);
//                $ub->setId($social_user['id']);
//                $ub->setAction($social_user['type']);
//
//                $social_users[] = $ub;
//            }
//        }
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

        if ($objectId == null) {
            return [];
        }

        $objectId = $this->cleanObjectId($objectId);
        $formatted_res = array();

        try {
            if (substr($objectId, 0, 1) === '#') {// hashtag search
                $objectId = str_replace('#', '%23', $objectId);
                $json = file_get_contents($this->tw . '1.1/search/tweets.json?q=' . $objectId, false, $this->context);
            } else {
                $json = file_get_contents($this->tw . '1.1/statuses/user_timeline.json?count=10&screen_name=' . $objectId, false, $this->context);
            }

            $res = json_decode($json, true);
            if(isset($res['statuses'])) {
                $res = $res['statuses'];
            }
            foreach($res as $key => $value) {
                try {
                    // Check if the array returned is not related to content (e.g. stats array)
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
        // Di default inserisco sul feed
        $post = strip_tags($content['content']['body']);
        if ($content['content']['main_url'] != null) {
            $post .= " " . $content['content']['main_url'];
        }

        $data = [
            'title' => $content['content']['title'],
            'message' => $post,
        ];


        $response = $this->fb->post('me/feed', $data);

        $nodeId = $response->getGraphNode()->getField('id');

        $info['id'] = $nodeId;
        $info['url'] = 'http://www.twitter.com/' . $nodeId;

        return $info;

    }

    public function update($content, $objectId)
    {
        return "Ho scritto " . $content . " su " . $objectId;
    }

    /**
     * @param null $objectId
     * @return \Twitter\TwitterResponse
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

        // Necessary only if are authenticating a page, not a profile
        if(isset($data['token'])) {
            $client = $this->fb->getOAuth2Client();

            try {
                // Returns a long-lived access token
                $accessToken = $client->getLongLivedAccessToken($data['token']);
            } catch(TwitterSDKException $e) {
                // There was an error communicating with Graph
                echo $e->getMessage();
                exit;
            }

            $data['longlivetoken'] = $accessToken->getValue();
        }

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
        } catch(TwitterResponseException $e) {
            $stats['likes'] = [];
            $stats['likes_number'] = 0;
        } catch(TwitterSDKException $e) {
            // When validation fails or other local issues
            echo 'Twitter SDK returned an error: ' . $e->getMessage();
            $stats = [];
        }

        try {
            $statRequest = '/' . $objectId . '/comments';

            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);
            $stats['comment_number'] = count($response->getDecodedBody()['data']);
        } catch(TwitterResponseException $e) {
            $stats['comment_number'] = 0;
        } catch(TwitterSDKException $e) {
            // When validation fails or other local issues
            echo 'Twitter SDK returned an error: ' . $e->getMessage();
            $stats = [];
        }


        try {
            $statRequest = '/' . $objectId . '/sharedposts';
            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);
            $stats['sharedposts'] = count($response->getDecodedBody()['data']);

        } catch(TwitterResponseException $e) {
            $stats['sharedposts'] = 0;
        } catch(TwitterSDKException $e) {
            // When validation fails or other local issues
            echo 'Twitter SDK returned an error: ' . $e->getMessage();
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

        } catch(TwitterResponseException $e) {
            $stats['insights'] = [];
        } catch(TwitterSDKException $e) {
            // When validation fails or other local issues
            echo 'Twitter SDK returned an error: ' . $e->getMessage();
            $stats = [];
        }

        return $stats;
    }

    public function comments($objectId, $operation = 'r', $content = null)
    {
        if ($this->objectId == null) {
            return [];
        }

        try {
            $statRequest = '/' . $objectId . '/comments';

            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);

            return $response->getDecodedBody()['data'];
        } catch(TwitterResponseException $e) {
            return [];
        } catch(TwitterSDKException $e) {
            // When validation fails or other local issues
            echo 'Twitter SDK returned an error: ' . $e->getMessage();
            return [];
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
        } catch(TwitterResponseException $e) {
            return [];
        } catch(TwitterSDKException $e) {
            // When validation fails or other local issues
            echo 'Twitter SDK returned an error: ' . $e->getMessage();
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
        } catch(TwitterResponseException $e) {
            return [];
        } catch(TwitterSDKException $e) {
            // When validation fails or other local issues
            echo 'Twitter SDK returned an error: ' . $e->getMessage();
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

}