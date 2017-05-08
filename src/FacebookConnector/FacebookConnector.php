<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\FacebookConnector;


use Facebook\Facebook;
use Facebook\FacebookRequest;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Exceptions\FacebookResponseException;
use Cake\Network\Http\Client;
use WR\Connector\ConnectorBean;
use WR\Connector\ConnectorUserBean;
use Cake\Log\Log;
class FacebookConnector extends Connector implements IConnector
{
    protected $fb;
    protected $longLivedAccessToken;
    protected $accessToken;
    protected $appSecret;

    private $feedLimit;
    private $objectId;

    function __construct($params)
    {
        $config = json_decode(file_get_contents('appdata.cfg', true), true);

        $this->fb = new Facebook([
            'app_id' => $config['app_id'], //'1561093387542751',
            'app_secret' =>  $config['app_secret'], //'0c081fec3c3b71d6c8bdf796f9868f03',
            'default_graph_version' =>  $config['default_graph_version'] //'v2.6',
        ]);

        $this->accessToken = $config['app_id'];
        $this->appSecret = $config['app_secret'];

        if ($params != null) {
            if (isset($params['longlivetoken']) && $params['longlivetoken'] != null) {
                $this->longLivedAccessToken = $params['longlivetoken'];
                $this->fb->setDefaultAccessToken($this->longLivedAccessToken);
            }

            $this->objectId = isset($params['pageid']) ? $params['pageid'] : '';

            $this->feedLimit = isset($params['feedLimit']) && $params['feedLimit'] != null ? $params['feedLimit'] : 20;
        }

        $debugTokenCommand = 'https://graph.facebook.com/debug_token?input_token='.$this->longLivedAccessToken.'&amp;access_token='.$this->accessToken;
        $http = new Client();
        $response = $http->get($debugTokenCommand);
        if($response->code !== 200) {
            $error = ['Error' => $response->code, 'Message' => $response->headers['WWW-Authenticate']];
            //debug($error); die;
            return $error;
        }

    }

    /**
     * @param $config
     * @return string
     */
    public function connect($config)
    {
        return "connect";
    }

    /**
     * Read a Facebook entity
     *
     * @param null $objectId
     * @return array
     */
    public function read($objectId = null)
    {
        // Read complete page feed
        if ($objectId == null) $objectId = $this->objectId;

        if ($objectId == null) {
            return [];
        }

        $streamToRead = '/' . $objectId . '/feed/?fields=id,type,created_time,message,story,picture,full_picture,link,attachments{url,type},reactions,shares,comments{from{name,picture,link},created_time,message,like_count,comments},from{name,picture}&limit=' . $this->feedLimit;
        $response = $this->fb->sendRequest('GET', $streamToRead);
        $data = $response->getDecodedBody()['data'];

        $data['social_users'] = [];

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

        $urlToRead = "https://graph.facebook.com/" . $objectId . "?fields=posts&limit=" . $this->feedLimit . "&access_token=" . $this->accessToken . "|" . $this->appSecret;
        $http = new Client();
        $response = $http->get($urlToRead);
        $data = $response->json;
        $formattedResult = $this->format_result($data);
        return($formattedResult);
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
        $info['url'] = 'http://www.facebook.com/' . $nodeId;

        return $info;

    }

    public function update($content, $objectId)
    {
        return "Ho scritto " . $content . " su " . $objectId;
    }

    /**
     * @param null $objectId
     * @return \Facebook\FacebookResponse
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
            } catch(FacebookSDKException $e) {
                // There was an error communicating with Graph
                echo $e->getMessage();
                exit;
            }

            $data['longlivetoken'] = $accessToken->getValue();
        }

        return $data;
    }

    /**
     * @param $objectId
     * @return array
     */
    public function stats($objectId)
    {
        if ($objectId == null) $objectId = $this->objectId;

        if ($objectId == null) {
            return [];
        }

        $stats = [];

        try {
            $statRequest = '/' . $objectId . '/likes';
            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);
            $stats['likes_number'] = count($response->getDecodedBody()['data']);
            $stats['likes'] = $response->getDecodedBody()['data'];
        } catch(FacebookResponseException $e) {
            $stats['likes'] = [];
            $stats['likes_number'] = 0;
        } catch(FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            $stats = [];
        }

        try {
            $statRequest = '/' . $objectId . '/comments';

            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);
            $stats['comment_number'] = count($response->getDecodedBody()['data']);
        } catch(FacebookResponseException $e) {
            $stats['comment_number'] = 0;
        } catch(FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            $stats = [];
        }


        try {
            $statRequest = '/' . $objectId . '/sharedposts';
            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);
            $stats['sharedposts'] = count($response->getDecodedBody()['data']);

        } catch(FacebookResponseException $e) {
            $stats['sharedposts'] = 0;
        } catch(FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
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

        } catch(FacebookResponseException $e) {
            $stats['insights'] = [];
        } catch(FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            $stats = [];
        }

        return $stats;
    }

    /**
     * @param $objectId The object where you want to read from or write to the comments. More information https://developers.facebook.com/docs/graph-api/reference/v2.9/object/comments
     * @param $operation The operation requested, 'r' stand for read, 'w' stand for write
     * @param $content
     * @return array
     */
    public function comments($objectId, $operation = 'r', $content = null)
    {
        if ($objectId == null) $objectId = $this->objectId;

        if ($objectId == null) {
            return [];
        }

        // In case of write first write comment than read the comment
        if($operation === 'w' && !empty($content)) {
            try {
                $this->fb->setDefaultAccessToken($this->longLivedAccessToken);
                $url = '/' . $objectId . '/comments';
                $data = [
                    'message' => strip_tags($content['comment']),
                ];
                $response = $this->fb->post($url, $data);

                $commentId = $response->getDecodedBody()['id'];
                $commentRequest = '/' . $commentId . '?fields=message,created_time,like_count,from{name,picture,link}';

                $request = $this->fb->request('GET', $commentRequest);
                $response = $this->fb->getClient()->sendRequest($request);

                return $response->getDecodedBody();
            } catch(FacebookResponseException $e) {
                Log::write('debug', $e);
                return [];
            }
        }


        // In case of update first write comment than read the comment
        if($operation === 'u' && !empty($content)) {
            try {
                $this->fb->setDefaultAccessToken($this->longLivedAccessToken);
                $url = '/' . $objectId;

                $data = [
                    'message' => strip_tags($content['comment']),
                ];
                $this->fb->post($url, $data);

                $url .= '?fields=message,created_time,like_count,from{name,picture,link}';
                $request = $this->fb->request('GET', $url);
                $response = $this->fb->getClient()->sendRequest($request);
                return $response->getDecodedBody();
            } catch(FacebookResponseException $e) {
                Log::write('debug', $e);
                return [];
            }
        }


        // In case of delete
        if($operation === 'd') {
            try {
                $this->fb->setDefaultAccessToken($this->longLivedAccessToken);
                $url = '/' . $objectId;

                $request = $this->fb->request('DELETE', $url);
                $this->fb->getClient()->sendRequest($request);
                return [];
            } catch(FacebookResponseException $e) {
                Log::write('debug', $e);
                return [];
            }
        }

        try {
            $statRequest = '/' . $objectId . '/comments';

            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);
            return $response->getDecodedBody()['data'];

        } catch(FacebookResponseException $e) {
            Log::write('debug', $e);
            return [];
        } catch(FacebookSDKException $e) {
            // When validation fails or other local issues
            Log::write('debug', $e);
            return [];
        }
    }


    /**
     * @param $objectId
     * @param $fromDate
     * @return array
     */
    public function commentFromDate($objectId, $fromDate) {
        if ($objectId == null) $objectId = $this->objectId;

        if ($objectId == null) {
            return [];
        }

        try {
            $statRequest = '/' . $objectId . '/comments';

            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);
            return $response->getDecodedBody()['data'];
        } catch(FacebookResponseException $e) {
            return [];
        } catch(FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            return [];
        }
    }

    /**
     * @param $objectId
     * @return array
     */
    public function user($objectId)
    {
        if ($objectId == null) $objectId = $this->objectId;

        if ($objectId == null) {
            return [];
        }
        //1165594993
        //email,about,age_range,birthday,currency,education,favorite_athletes,favorite_teams,hometown,about,birthday,inspirational_people,interested_in,languages,location,meeting_for,political,quotes,relationship_status,religion,significant_other,sports,website,work
        //id,name,first_name,last_name,middle_name,gender,cover,currency,devices,link,locale,name_format,timezone
        try {
            $request = $this->fb->sendRequest('GET', '/' . $objectId);
            $response = $this->fb->getClient()->sendRequest($request);
            //debug($request); die;
            return $response->getDecodedBody();
        } catch(FacebookResponseException $e) {
            debug($e); die;
            return [];
        } catch(FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            return [];
        }
    }

    /**
     * @param $content
     */
    public function add_user($content)
    {

    }

    /**
     * @param $content
     */
    public function update_categories($content)
    {

    }



    /**
     * Get fan from the stream
     *
     * @param null $objectId
     * @return array
     */
    public function captureFan($objectId = null)
    {
        // Read complete page feed
        if ($objectId == null) $objectId = $this->objectId;

        if ($objectId == null) {
            return [];
        }

        $streamToRead = '/' . $objectId . '/feed/?fields=id,type,created_time,message,story,picture,full_picture,link,attachments{url,type},reactions,shares,comments{from{name,picture,link},created_time,message,like_count,comments},from{name,picture}&limit=' . $this->feedLimit;
        $response = $this->fb->sendRequest('GET', $streamToRead);
        $data = $response->getDecodedBody()['data'];

        // Append users that have taken an action on the page
        $social_users = array();

        foreach($data as $d) {
            $ancestor_body = isset($d['message']) ? $d['message'] : (isset($d['story']) ? $d['story'] : '');

            if(isset($d['reactions'])) {
                foreach($d['reactions']['data'] as $social_user) {
                    $ub = new ConnectorUserBean();
                    $ub->setName($social_user['name']);
                    $ub->setId($social_user['id']);
                    $ub->setAction($social_user['type']);
                    $ub->setContentId($d['id']);
                    $ub->setText('');

                    $ub->setAncestorBody($ancestor_body);

                    $ub = $this->getUserExtraData($social_user['id'], $ub);

                    $social_users[] = $ub;
                }
            }

            if(isset($d['comments'])) {
                foreach($d['comments']['data'] as $social_user) {
                    $ub = new ConnectorUserBean();
                    $ub->setName($social_user['from']['name']);
                    $ub->setId($social_user['from']['id']);
                    $ub->setAction('COMMENT');
                    $ub->setContentId($d['id']);
                    $ub->setDate($social_user['created_time']);
                    $ub->setText($social_user['message']);

                    $ub->setAncestorBody($ancestor_body);

                    $ub = $this->getUserExtraData($social_user['id'], $ub);

                    $social_users[] = $ub;

                    if(isset($social_user['comments'])) {
                        foreach($social_user['comments']['data'] as $sub_comment) {
                            $ub = new ConnectorUserBean();
                            $ub->setName($sub_comment['from']['name']);
                            $ub->setId($sub_comment['from']['id']);
                            $ub->setAction('COMMENT');
                            $ub->setContentId($d['id']);
                            $ub->setDate($sub_comment['created_time']);
                            $ub->setText($sub_comment['message']);

                            $ub->setAncestorBody($social_user['message']);

                            $ub = $this->getUserExtraData($social_user['id'], $ub);

                            $social_users[] = $ub;
                        }
                    }
                }
            }
        }

        $data['social_users'] = $social_users;

        //debug($data); die;
        return($data);
    }

    /**
     * @param $posts
     * @return array
     */
    private function format_result($posts) {
        $beans = array();
        foreach($posts['posts']['data'] as $post) {
            $element =  new ConnectorBean();
            if(!empty($post['message']))
                $element->setBody($post['message']);
            else
                $element->setBody($post['story']);

            if(!empty($post['story']))
                $element->setTitle($post['story']);

            $element->setIsContentMeaningful(1);
            $element->setCreationDate($post['created_time']);
            $element->setMessageId($post['id']);
            $element->setAuthor('');

            //https://twitter.com/RadioNightwatch/status/827460856128614401
            $uri = 'https://www.facebook.com/' . $post['id'];
            $element->setUri($uri);

            $element->setRawPost($post);

            $beans[] = $element;
        }
        return $beans;
    }

    private function getUserExtraData($userId, ConnectorUserBean $ub) {
        try {
            $request = $this->fb->request('GET', '/' . $userId . '/?fields=id,name,first_name,last_name,middle_name,gender,cover,currency,devices,link,locale');
            $response = $this->fb->getClient()->sendRequest($request);
            $extraData = $response->getDecodedBody();

            $ub->setFirstname($this->blankForEmpty($extraData['first_name']));
            $ub->setLastname($this->blankForEmpty($extraData['last_name']));
            $ub->setGender($this->blankForEmpty($extraData['gender']));
            $ub->setCoverimage($this->blankForEmpty($extraData['cover']['source']));
            $ub->setLocale($this->blankForEmpty($extraData['locale']));
            $ub->setCurrency($this->blankForEmpty($extraData['currency']));
            //$ub->setDevices($this->blankForNotSet($extraData['first_name']));

        } catch(FacebookResponseException $e) {

        } catch(FacebookSDKException $e) {

        }

        return $ub;
    }

    private function blankForEmpty(&$var) {
        return !empty($var) ? $var : '';
    }
}