<?php

/**
 * Created by Leandro Cesinaro.
 * User: user
 * Date: 08/05/2020
 * Time: 15:31
 */

namespace WR\Connector\GoogleConnector;

use App\Controller\Component\UtilitiesComponent;
use Cake\I18n\Time;
use Google\Auth\CredentialsLoader;
use Google\Auth\OAuth2;
use Google\ApiCore\ApiException;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use Cake\Network\Http\Client;
use WR\Connector\ConnectorBean;
use WR\Connector\ConnectorUserBean;
use Cake\Log\Log;
use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

class GoogleConnector extends Connector implements IConnector {

    /**
     * @var string the OAuth2 scope for the Google Ads API
     * @see https://developers.google.com/google-ads/api/docs/oauth/internals#scope
     */
    private const SCOPE = 'https://www.googleapis.com/auth/adwords';

    /**
     * @var string the Google OAuth2 authorization URI for OAuth2 requests
     * @see https://developers.google.com/identity/protocols/OAuth2InstalledApp#step-2-send-a-request-to-googles-oauth-20-server
     */
    private const AUTHORIZATION_URI = 'https://accounts.google.com/o/oauth2/v2/auth';

    /**
     * @var int for limiting paging requests
     */
    private const PAGE_SIZE = 1000;

    /**
     * @var OAuth2 $google
     */
    protected $google;
    protected $accessToken;
    protected $refreshToken;
    protected $developerKey;
    protected $clientId;
    protected $clientSecret;
    protected $objectGoogleId;
    protected $connectorUsersSettingsID;
    private $feedLimit;
    private $objectId;
    private $since;
    private $until;
    var $error = false;

    function __construct($params) {
        $config = json_decode(file_get_contents('appdata_dev.cfg', true), true);

        $this->google = new OAuth2([
            'clientId' => $config['client_id'],
            'clientSecret' => $config['client_secret'],
            'authorizationUri' => self::AUTHORIZATION_URI,
            'redirectUri' => SUITE_SOCIAL_LOGIN_CALLBACK_URL,
            'tokenCredentialUri' => CredentialsLoader::TOKEN_CREDENTIAL_URI,
            'scope' => self::SCOPE
        ]);

        $this->clientId = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->developerKey = $config['developer_key'];

        if (isset($params['connectorUsersSettingsID']) ?? null)
            $this->connectorUsersSettingsID = $params['connectorUsersSettingsID'];

        if ($params != null) {
            if(!empty($params['refreshtoken'])) {
                $this->refreshToken = $params['refreshtoken'];
            }

            $this->accessToken = isset($params['token']) ? $params['token'] : '';
            $this->objectId = isset($params['customerid']) ? $params['customerid'] : '';
            $this->objectGoogleId = isset($params['customerid']) ? $params['customerid'] : '';

            $this->feedLimit = isset($params['feedLimit']) && $params['feedLimit'] != null ? $params['feedLimit'] : 20;
            $this->since = isset($params['since']) ? $params['since'] : null; // Unix timestamp since
            $this->until = isset($params['until']) ? $params['until'] : null; // Unix timestamp until
        }

        $debugTokenCommand = 'https://oauth2.googleapis.com/tokeninfo?access_token=' . $this->accessToken;

        $http = new Client();
        $response = $http->get($debugTokenCommand);

        if ($response->code !== 200) {
            $this->error = 2;
            return ['Error' => $response->code, 'Message' => $response->reasonPhrase];
        }
    }

    /**
     * @param $params
     * @return string
     */
    public function connect($params) {

        $this->google->setState($params['query']);

        // Redirect the user to the authorization URL.
        $config = [
            // Set to 'offline' if you require offline access.
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'prompt' => 'consent',
        ];

        $loginUrl = $this->google->buildFullAuthorizationUri($config);

        return '<a class="btn btn-block btn-social btn-google" href="' . htmlspecialchars($loginUrl) . '"><span class="fa fa-google"></span> Connect with Google</a>';

    }

    /**
     * @return bool
     */
    public function isLogged() {
        $logged = true;
        $debugTokenCommand = 'https://oauth2.googleapis.com/tokeninfo?access_token=' . $this->accessToken;

        $http = new Client();
        $response = $http->get($debugTokenCommand);
        $body = $response->json;

        if (isset($body['error'])) {
             $this->setError($body['error_description']);
             $logged = false;
             //debug($error); die;
        }

        return $logged;
    }

    /**
     * @return string
     */
    public function getAccounts() {
        return "accounts";
    }

    /**
     * Read a Facebook entity
     *
     * @param null $objectId
     * @return array
     */
    public function read($objectId = null) {
        return null;
    }

    /**
     * @param null $objectId
     * @return array
     */
    public function readPublicPage($objectId = null) {
        return null;
    }

    /**
     * @return array
     */
    public function write($content) {
        return null;
    }

    public function update($content, $objectId) {
        return "Ho scritto " . $content . " su " . $objectId;
    }

    /**
     * @param null $objectId
     * @return array
     */
    public function delete($objectId = null) {
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
     * @param $objectId
     * @return array
     */
    public function stats($objectId) {
        return null;
    }

    /**
     * @param $objectId The object where you want to read from or write to the comments.
     * @param $operation The operation requested, 'r' stand for read, 'w' stand for write
     * @param $content
     * @return array
     */
    public function comments($objectId, $operation = 'r', $content = null) {
        return null;
    }

    /**
     * @param $objectId
     * @param $fromDate
     * @return array
     */
    public function commentFromDate($objectId, $fromDate) {
        return null;
    }

    /**
     * @param $objectId
     * @return array
     */
    public function user($objectId) {
        return null;
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

        $data = array();

        if (!isset($_GET['state']) && $this->google->getState() !== $_GET['state']) {
            header('HTTP/1.0 401 Unauthorized');
            exit;
        }

        if(isset($_GET['code'])){
            try {
                $this->google->setCode($_GET['code']);
                $authToken = $this->google->fetchAuthToken();
                $this->google->verifyIdToken();
            }catch (\Exception $e){
                echo 'Google Auth returned an error: ' . $e->getMessage();
                exit;
            }
        }else{
            header('HTTP/1.0 400 Bad Request');
            exit;
        }

        // Logged in
        $data['token'] = $authToken['access_token'];
        $data['refreshtoken'] = $authToken['refresh_token'];

        return $data;
    }

    /**
     * Get fan from the stream
     *
     * @param null $objectId
     * @return array
     */
    public function captureFan($objectId = null) {
        return null;
    }

    public function setError($message) {
        return $message;
    }

}
