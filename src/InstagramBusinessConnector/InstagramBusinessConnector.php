<?php

/**
 * Created by Leandro Cesinaro
 * User: user business
 */

namespace WR\Connector\InstagramBusinessConnector;

use Cake\I18n\Time;
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
use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

class InstagramBusinessConnector extends Connector implements IConnector {

    /** @var Facebook\Facebook $fb */
    protected $fb;
    protected $longLivedAccessToken;
    protected $accessToken;
    protected $appSecret;
    protected $objectFbId;
    protected $connectorUsersSettingsID;
    private $feedLimit;
    private $objectId;
    private $objectIgId;
    private $since;
    private $until;
    var $error = false;

    function __construct($params) {
        $config = json_decode(file_get_contents('appdata_dev.cfg', true), true);

        $this->fb = new Facebook([
            'http_client_handler' => 'stream', // do not use Guzzle 5.*, prefer PHP streams
            'app_id' => $config['app_id'], //'1561093387542751',
            'app_secret' => $config['app_secret'], //'0c081fec3c3b71d6c8bdf796f9868f03',
            'default_graph_version' => $config['default_graph_version'] //'v2.6',
        ]);

        $this->accessToken = $config['app_id'];
        $this->appSecret = $config['app_secret'];

        // FIXME tolgo il notice, ma è cambiato qualcosa da qualche parte
        if (@isset($params['connectorUsersSettingsID']))
            $this->connectorUsersSettingsID = $params['connectorUsersSettingsID'];

        if ($params != null) {
            if (isset($params['longlivetoken']) && $params['longlivetoken'] != null) {
                $this->longLivedAccessToken = $params['longlivetoken'];
                $this->fb->setDefaultAccessToken($this->longLivedAccessToken);
            }

            $this->objectId = isset($params['pageid']) ? $params['pageid'] : '';
            $this->objectFbId = isset($params['pageid']) ? $params['pageid'] : '';
            $this->objectIgId = isset($params['igbusinessid']) ? $params['igbusinessid'] : '';

            $this->feedLimit = isset($params['feedLimit']) && $params['feedLimit'] != null ? $params['feedLimit'] : 20;
            $this->since = isset($params['since']) ? $params['since'] : null; // Unix timestamp since
            $this->until = isset($params['until']) ? $params['until'] : null; // Unix timestamp until
        }

//    $debugTokenCommand = 'https://graph.facebook.com/debug_token?input_token='.$this->longLivedAccessToken.'&amp;access_token='.$this->accessToken;
        $debugTokenCommand = 'https://graph.facebook.com/me?access_token=' . $this->longLivedAccessToken;

        $http = new Client();
        $response = $http->get($debugTokenCommand);
        $body = $response->json;

//    if ($body['error']) {
//      $this->error = $body;
//      $error = ['Error' => $body['error']['code'], 'Message' => $body['error']['message']];
//      //debug($error); die;
//      return $error;
//    }

        if ($response->code !== 200) {
            $this->error = 2;
            $error = ['Error' => $response->code, 'Message' => $response->headers['WWW-Authenticate']];
            //debug($error); die;
            return $error;
        }
    }

    /**
     * @param $config
     * @return string
     */
    public function connect($config) {

        $helper = $this->fb->getRedirectLoginHelper();

        // Vecchi permessi
        // $permissions = ['publish_actions', 'read_insights', 'public_profile', 'email', 'user_friends', 'manage_pages', 'publish_pages']; // Optional permissions
        $permissions = ['business_management', 'manage_pages', 'publish_pages', 'instagram_basic', 'instagram_manage_comments', 'instagram_manage_insights']; // Optional permissions
        $loginUrl = $helper->getLoginUrl(SUITE_SOCIAL_LOGIN_CALLBACK_URL, $permissions) . "&state=" . $config['query'];

        return '<a class="btn btn-block btn-social btn-facebook" href="' . htmlspecialchars($loginUrl) . '"><span class="fa fa-facebook"></span> Connect with Facebook</a>';
        
    }

    /**
     * @return bool
     */
    public function isLogged() {
        $logged = false;

        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = $this->fb->get('/me?fields=id,name', $this->longLivedAccessToken);
            $logged = true;
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $logged = false;
            $this->setError($e->getMessage());
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            $logged = false;
            $this->setError($e->getMessage());
        }
//    $user = $response->getGraphUser();
        return $logged;
    }

    /**
     * @return array
     */
    public function getAccounts() {

        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = $this->fb->get('/me?fields=accounts.limit(255){instagram_business_account,name,access_token}', $this->longLivedAccessToken);
            $logged = true;
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $logged = false;
//        echo 'Graph returned an error: ' . $e->getMessage();
//        exit;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            $logged = false;
//        echo 'Facebook SDK returned an error: ' . $e->getMessage();
//        exit;
        }

        return $response->getDecodedBody();
    }

    /**
     * @return array
     */
    public function getUser() {

        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = $this->fb->get('/me?fields=id,name', $this->longLivedAccessToken);
            $logged = true;
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $logged = false;
            //        echo 'Graph returned an error: ' . $e->getMessage();
            //        exit;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            $logged = false;
            //        echo 'Facebook SDK returned an error: ' . $e->getMessage();
            //        exit;
        }

        return $response->getDecodedBody();
    }

    /**
     * @param null $objectId
     * @return array
     */
    public function getIGAccount($objectId = null) {
      // Read instagram business account connected to the Fb page
      if ($objectId == null)
        $objectId = $this->objectId;

      if ($objectId == null) {
        return [];
      }

      try {
        // Returns a `Facebook\FacebookResponse` object
        $response = $this->fb->get('/'.$objectId.'?fields=instagram_business_account', $this->longLivedAccessToken);
        $logged = true;
      } catch (\Facebook\Exceptions\FacebookResponseException $e) {
        $logged = false;
  //        echo 'Graph returned an error: ' . $e->getMessage();
  //        exit;
      } catch (\Facebook\Exceptions\FacebookSDKException $e) {
        $logged = false;
  //        echo 'Facebook SDK returned an error: ' . $e->getMessage();
  //        exit;
      }

      return $response->getDecodedBody();
    }

    /**
     * Read a Facebook entity
     *
     * @param null $objectIgId
     * @return array
     */
    public function read($objectIgId = null) {

        // Read complete page feed
        if ($objectIgId == null)
            $objectIgId = $this->objectIgId;

        if ($objectIgId == null) {
            return [];
        }

        $limitString = '&limit=' . $this->feedLimit;
        if (!empty($this->since) && !empty($this->until)) {
            $limitString = '&since=' . $this->since . '&until=' . $this->until;
        }

        $streamToRead = '/' . $objectIgId . '?fields=media' . $limitString;
        try {
          $response = $this->fb->get($streamToRead);

          $result = [];
          foreach ($response->getGraphNode()->getField('media') as $key => $media_obj) {
            $streamToRead = '/' . $media_obj['id']
              . '?fields=caption,like_count,media_type,media_url,owner,permalink,thumbnail_url,timestamp,comments_count,'
              . 'comments.limit(10){user,username,timestamp,text,like_count,id,replies{user,username,timestamp,text,like_count,id}}'
              . $limitString;

            $response = $this->fb->get($streamToRead);

            $row = [];
            $row['id'] = $response->getGraphNode()->getField('id');
            $row['media_type'] = $response->getGraphNode()->getField('media_type');
            $row['timestamp'] = new Time($response->getGraphNode()->getField('timestamp'));
            $row['caption'] = $response->getGraphNode()->getField('caption');
            $row['thumbnail_url'] = $response->getGraphNode()->getField('thumbnail_url') == null ? null : $response->getGraphNode()->getField('thumbnail_url');
            $row['media_url'] = $response->getGraphNode()->getField('media_url');
            $row['permalink'] = $response->getGraphNode()->getField('permalink');
            if($response->getGraphNode()->getField('owner')->getField('id') == $objectIgId ){
              $owner = $this->fb->get('/' . $objectIgId . '?fields=name,username,profile_picture_url');
              $row['owner'] = $owner->getGraphNode()->asArray();
            }else{
              $row['owner'] = $response->getGraphNode()->getField('owner');
            }
            $row['comments'] = $response->getGraphNode()->getField('comments') == null ? [] : $response->getGraphNode()->getField('comments')->asArray();

            //get picture and name of commentator
            /* rallenta il caricamento
            if(count($row['comments']) > 0) {
              foreach ($row['comments'] as $id => $comments) {
                  $extra_data = $this->getBusinessDiscovery($objectIgId, $comments['username']);
                  if($extra_data != null){
                    $row['comments'][$id] += ['extra' => $extra_data];
                  }
              }
            }*/

            if(count($row['comments']) > 0 && $response->getGraphNode()->getField('replies') != null) {
              array_push($row['comments'],$response->getGraphNode()->getField('replies')->asArray());
            }
            $row['comments_count'] = $response->getGraphNode()->getField('comments_count');
            $row['like_count'] = $response->getGraphNode()->getField('like_count');
            $result[] = $row;

          }

          $result['social_users'] = [];
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
          $result = [];
          $result['error'] = $e->getMessage();
        }

        //formattato per visualizzazione in comments.ctp
        foreach ($result as $id => $value) {
            if (Hash::extract($result, $id . '.comments') != null) {
                $val = Hash::extract($result, $id . '.comments');
                $result = Hash::remove($result, $id . '.comments');
                $result = Hash::insert($result, $id . '.comments.igdata', $val);
            }
        }

        return $result;
    }

    /**
     * @param null $objectId
     * @return array
     */
    //TODO: for ContentMachine e LibSocial
    public function readPublicPage($objectId = null) {
        // Read complete public page feed
        if ($objectId == null)
            $objectId = $this->objectId;

        if ($objectId == null) {
            return [];
        }

        $urlToRead = "https://graph.facebook.com/" . $objectId . "?fields=posts&limit=" . $this->feedLimit . "&access_token=" . $this->accessToken . "|" . $this->appSecret;
        $http = new Client();
        $response = $http->get($urlToRead);
        $data = $response->json;

        if (isset($data['error']))
          return [];
        Log::error('InstagramBusinessConnector readPublicPage urlToRead"'. print_r($urlToRead,true));
        $formattedResult = $this->format_result($data);
        return ($formattedResult);
    }

    /**
     * @return array
     */
    public function write($content)
    {
    }

    public function update($content, $objectId) {
        return "Ho scritto " . $content . " su " . $objectId;
    }

    /**
     * @param null $objectId
     * @return \Facebook\FacebookResponse
     */
    public function delete($objectId = null) {
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

    /**
     * @param $objectIgId
     * @return array
     * @link https://developers.facebook.com/docs/graph-api/reference/v3.0/insights facebook api insights
     */
    public function stats($objectIgId) {
        if ($objectIgId == null)
          $objectIgId = $this->objectIgId;

        if ($objectIgId == null)
            return [];

        try {
            $statRequest = '/' . $objectIgId . '?fields=like_count,comments_count';
            $response = $this->fb->get($statRequest);

            $stats['likes_number'] = $response->getGraphNode()->getField('like_count');
            $stats['comment_number'] = $response->getGraphNode()->getField('comments_count');

            $stats['insights'] = [];
            $statRequest = '/' . $objectIgId
                . '/?fields=insights.metric('
                . 'post_engaged_users'
                . ',post_engaged_fan'
                . ',post_negative_feedback'
                . ',post_negative_feedback_unique'
                . ',post_impressions'
                . ',post_impressions_organic'
                . ',post_impressions_unique'
                . ',post_impressions_paid'
                . ',post_clicks'
                . ',post_clicks_unique'
                . ',post_reactions_by_type_total'
                . '){values,name}';
            $response = $this->fb->get($statRequest);
            $ge = $response->getGraphNode()->getField('insights');

            $insights = $ge->asArray();
            $myInsights = [];
            foreach ($insights as $key => $value) {
                if (isset($value['values']) && isset($value['values'][0]['value'])) {
                    if ($value['name'] == 'post_reactions_by_type_total') {
                        foreach ($value['values'][0]['value'] as $rname => $rvalue)
                            $myInsights[$rname] = $rvalue;
                    } else
                        $myInsights[$value['name']] = $value['values'][0]['value'];
                }
            }

            $stats['insights'] = $myInsights;
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
        }

        return $stats;
    }

    /**
     * @param $objectIgId The object where you want to read from or write to the comments. More information https://developers.facebook.com/docs/graph-api/reference/v2.9/object/comments
     * @param $operation The operation requested, 'r' stand for read, 'w' stand for write
     * @param $content
     * @return array
     */
    public function comments($objectIgId, $operation = 'r', $content = null) {
        if ($objectIgId == null)
          $objectIgId = $this->objectIgId;

        if ($objectIgId == null) {
            return [];
        }

        // In case of write first write comment than read the comment
        if ($operation === 'w' && !empty($content)) {
            try {
                $this->fb->setDefaultAccessToken($this->longLivedAccessToken);
                $url = ($content['type'] == "firstLevel") ? '/' . $objectIgId . '/comments?message=' : '/' . $objectIgId . '/replies?message=';
                $data = [
                  'message' => strip_tags($content['comment']),
                ];
                $response = $this->fb->post($url, $data);

                $commentId = $response->getDecodedBody()['id'];

                $commentRequest =  '/' . $commentId . '?fields=user,username,timestamp,text,like_count,id';

                $response = $this->fb->get($commentRequest);

                return $response->getGraphNode()->asArray();
            } catch (\Facebook\Exceptions\FacebookResponseException $e) {
                Log::write('debug', $e);
                return [];
            }
        }

        // Update operation not supported (we can only hide/show comments)

        // In case of delete
        if ($operation === 'd') {
            try {
                $this->fb->setDefaultAccessToken($this->longLivedAccessToken);
                $url = '/' . $objectIgId;

                $request = $this->fb->request('DELETE', $url);
                $this->fb->getClient()->sendRequest($request);
                return true;
            } catch (\Facebook\Exceptions\FacebookResponseException $e) {
                Log::write('debug', $e);
                return false;
            }
        }

        try {
            $statRequest = '/' . $objectIgId ;

            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);
            return $response->getDecodedBody()['data'];
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            Log::write('debug', $e);
            return [];
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
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
        if ($objectId == null)
            $objectId = $this->objectId;

        if ($objectId == null) {
            return [];
        }

        try {
            $statRequest = '/' . $objectId . '/comments';

            $request = $this->fb->request('GET', $statRequest);
            $response = $this->fb->getClient()->sendRequest($request);
            return $response->getDecodedBody()['data'];
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            return [];
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            return [];
        }
    }

    /**
     * @param $objectId
     * @return array
     */
    public function user($objectId) {
        if ($objectId == null)
            $objectId = $this->objectId;

        if ($objectId == null) {
            return [];
        }

        try {
            // Returns a `Facebook\FacebookResponse` object
            $objectId = '/' . $objectId;

            $response = $this->fb->get(
                '/' . $objectId, $this->longLivedAccessToken//'{access-token}'
            );
            debug($response);
            die;
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            debug($e->getMessage());
            return [];
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            debug($e->getMessage());
            return [];
        }
        $graphNode = $response->getGraphNode();
        /* handle the result */
    }

    /**
     * @param $content
     */
    public function add_user($content) {
        
    }

    /**
     * @param $content
     */
    public function update_categories($content) {
        
    }

    public function callback($params) {

        $config = json_decode(file_get_contents('appdata_dev.cfg', true), true);
        $data = array();

        $helper = $this->fb->getRedirectLoginHelper();
        if (isset($_GET['state'])) {
            $helper->getPersistentDataHandler()->set('state', $_GET['state']);
        }

        try {
            $accessToken = $helper->getAccessToken();
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        if (!isset($accessToken)) {
            if ($helper->getError()) {
                header('HTTP/1.0 401 Unauthorized');
                echo "Error: " . $helper->getError() . "\n";
                echo "Error Code: " . $helper->getErrorCode() . "\n";
                echo "Error Reason: " . $helper->getErrorReason() . "\n";
                echo "Error Description: " . $helper->getErrorDescription() . "\n";
            } else {
                header('HTTP/1.0 400 Bad Request');
                echo 'Bad request';
            }
            exit;
        }

        // Logged in
        $data['token'] = $accessToken->getValue();

        // The OAuth 2.0 client handler helps us manage access tokens
        $oAuth2Client = $this->fb->getOAuth2Client();

        // Get the access token metadata from /debug_token
        $tokenMetadata = $oAuth2Client->debugToken($accessToken);

        // Validation (these will throw FacebookSDKException's when they fail)
        $tokenMetadata->validateAppId($config['app_id']); // Replace {app-id} with your app id
        // If you know the user ID this access token belongs to, you can validate it here
        //$tokenMetadata->validateUserId('123');
        $tokenMetadata->validateExpiration();

        if (!$accessToken->isLongLived()) {
            // Exchanges a short-lived access token for a long-lived one
            try {
                $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
                exit;
            }

            $data['longlivetoken'] = $accessToken->getValue();
//        echo '<h3>Long-lived</h3>';
//        debug($accessToken->getValue());
        }

        $client = $this->fb->getOAuth2Client();

        try {
            // Returns a long-lived access token
            $accessToken = $client->getLongLivedAccessToken($data['token']);
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            // There was an error communicating with Graph
            echo $e->getMessage();
            exit;
        }

        $data['longlivetoken'] = $accessToken->getValue();

        return $data;
    }

    /**
     * Get fan from the stream
     *
     * @param null $objectId
     * @return array
     */
    public function captureFan($objectId = null) {
        // Read complete page feed
        if ($objectId == null)
            $objectId = $this->objectId;

        if ($objectId == null) {
            return [];
        }

        try {

            $streamToRead = '/' . $objectId . '/feed/?fields=id,type,created_time,message,story,picture,full_picture,link,attachments{url,type},reactions,shares,comments{from{name,picture,link},created_time,message,like_count,comments},from{name,picture}&limit=' . $this->feedLimit;
            $response = $this->fb->sendRequest('GET', $streamToRead);

            $data = $response->getDecodedBody()['data'];

            // Append users that have taken an action on the page
            $social_users = array();

            foreach ($data as $d) {
                $ancestor_body = isset($d['message']) ? $d['message'] : (isset($d['story']) ? $d['story'] : '');

                if (isset($d['reactions'])) {
                    foreach ($d['reactions']['data'] as $social_user) {
                        $ub = new ConnectorUserBean();
                        $ub->setName($social_user['name']);
                        $ub->setId($social_user['id']);
                        $ub->setAction($social_user['type']);
                        $ub->setContentId($d['id']);
                        $ub->setText('');

                        $ub->setDate(Time::now()->toAtomString());

                        $ub->setAncestorBody($ancestor_body);

                        $ub = $this->getUserExtraData($social_user['id'], $ub);

                        $social_users[] = $ub;
                    }
                }

                if (isset($d['comments'])) {
                    foreach ($d['comments']['data'] as $social_user) {
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

                        if (isset($social_user['comments'])) {
                            foreach ($social_user['comments']['data'] as $sub_comment) {
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
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $data = [];
            $data['error'] = $e->getMessage();
        }

        return ($data);
    }

    /**
     * @param $posts
     * @return array
     */
    private function format_result($posts) {
        $beans = array();
        if(empty($posts['posts']['data'])|| empty($posts['posts'])){
            Log::error('InstagramBusinessConnector format_result "'. print_r($posts,true));
            return $beans;
        }

        foreach ($posts['posts']['data'] as $post) {
            $element = new ConnectorBean();
            if (!empty($post['message']))
                $element->setBody($post['message']);
            elseif (!empty($post['story'])) {
                $element->setBody($post['story']);
            }

            if (!empty($post['story']))
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

						$request = $this->fb->request('GET', '/' . $userId . '/?fields=id,name,first_name,last_name,gender,picture,locale');
            $response = $this->fb->getClient()->sendRequest($request);
            $extraData = $response->getDecodedBody();

            $ub->setFirstname($this->blankForEmpty($extraData['first_name']));
            $ub->setLastname($this->blankForEmpty($extraData['last_name']));
            $ub->setGender($this->blankForEmpty($extraData['gender']));
						$ub->setCoverimage($this->blankForEmpty($extraData['picture']['data']['url']));
            $ub->setLocale($this->blankForEmpty($extraData['locale']));
            $ub->setCurrency($this->blankForEmpty($extraData['currency']));
            //$ub->setDevices($this->blankForNotSet($extraData['first_name']));
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {

        } catch (\Facebook\Exceptions\FacebookSDKException $e) {

        }

        return $ub;
    }

    private function getBusinessDiscovery($objectIgId, $username) {

      // Read other IG Business accounts
      if ($objectIgId == null)
        $objectIgId = $this->objectIgId;

      if ($objectIgId == null) {
        return [];
      }

      $result = null;

      try {

        $request = '/' . $objectIgId . '/?fields=business_discovery.username('. $username .'){name,profile_picture_url,id}';
        $response = $this->fb->get($request);

        $result = $response->getGraphNode()->getField('business_discovery')->asArray();

      } catch (\Facebook\Exceptions\FacebookResponseException $e) {

      } catch (\Facebook\Exceptions\FacebookSDKException $e) {

      }

      return $result;
    }

    private function blankForEmpty(&$var) {
        return !empty($var) ? $var : '';
    }

    public function setError($message) {

        return $message;
    }

}
