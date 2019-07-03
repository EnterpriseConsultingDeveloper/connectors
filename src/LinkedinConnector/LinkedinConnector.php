<?php
/**
 * Created by Fabio Mugnano.
 * User: user
 * Date: 19/04/2019
 * Time: 12:06
 */

namespace WR\Connector\LinkedinConnector;

use WR\Connector\Connector;
use WR\Connector\ConnectorBean;
use WR\Connector\IConnector;
//use Cake\Network\Http\Client;
use Cake\Collection\Collection;
use App\Lib\Linkedin\Client;
use App\Lib\Linkedin\Scope;
use Cake\I18n\Time;
use Cake\Routing\Router;


class LinkedinConnector extends Connector implements IConnector
{

    protected $li;
    protected $app_client_key;
    protected $app_client_secret;
    protected $code;
    protected $access_token;
    protected $config;


    function __construct($params)
    {
        // debug($params);
        $this->config = json_decode(file_get_contents('appdata.cfg', true), true);

        $this->li = new Client(
            $this->config['client_id'],
            $this->config['client_secret']
        );

        if (!empty($params['access_token'])) {
            $this->li->setAccessToken($params['access_token']);

        }

        /*se gia conesso*/
        /*  $this->li = new Client(
              $this->config['client_id'],
              $this->config['client_secret']
          );
          $this->li->setAccessToken('AQVilpPRRzKG5o3lxHWKIQvbN4CLSqlC1sVv6BldgpPzPnMA_XImHO30aMZTF2L_qw7Oc7QylDWnFWrWtiZjKfgrD-RiOoR7FhU4DY7M9i2-YDBFlluwGdBFANiE6GpIpjpQA-RB-EiAriwc33x5IK4WxsDXu4ftG4DZJ5eFqgobsKLl7qb12qNzL7mpzzrZS0izq2Qd6w8yRP6bubzevTnzpLVFdaEUnMc3dx2deoHqJ_opz-jH1HWW4XfQN08FOQFV6WHLLRpTR0kbiJfejU7UP_sSUaAQK02nLxlqiS1x8x-t5jnWixm5NNgt1LNowmQ1JAkyjm7n5j8x7MlzRVZmUWEgpA');
  */
        return $this->li;

    }

    public function connect($params)
    {
        $this->li = new Client(
            $this->config['client_id'],
            $this->config['client_secret']
        );
        $redirect_uri = $params['redirect_uri'];

        $this->li->setRedirectUrl($redirect_uri);

        /* $scopes = [
             Scope::READ_BASIC_PROFILE,
             Scope::READ_EMAIL_ADDRESS,
             //Scope::MANAGE_COMPANY,
             Scope::SHARING,
         ];*/

        $scopes = explode(",", $this->config['scopes']);

        $loginUrl = $this->li->getLoginUrl($scopes); // get url on LinkedIn to start linking

        return $loginUrl;
    }


    public function getToken($code, $params)
    {
        $this->li = new Client(
            $this->config['client_id'],
            $this->config['client_secret']
        );
        /*$this->li->setRedirectUrl('https://social-dev.whiterabbit.online/socialLogin/linkedinResponse?c=YOAELKG1SG&ref=https://enterprise-dev.whiterabbit.online/connector_instance_channels/connect/24');
        $accessToken = $this->li->getAccessToken($code);
        debug($access_token);
        die;*/
        //$this->li->getOAuthApiRoot();
        $url = $this->li->myBuildUrl('accessToken', []);
        $params = [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'code' => $code,
            'redirect_uri' => $params['redirect_uri'],
            'grant_type' => 'authorization_code'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = array(
            "Content-Type: application/x-www-form-urlencoded",
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        $response = curl_exec($ch);

        curl_close($ch);
        $data = json_decode($response);
        if (!empty($data->access_token)) {
            $access_token = $data->access_token;
            $this->li->setAccessToken($access_token);
            /*$profile = $this->li->get(
                'people/~:(id,email-address,first-name,last-name)'
            );*/

            $profile = $this->li->apiV2(
                'me'
            );

            $result['error'] = false;
            $result['access_token'] = $access_token;
            $result['token_expires_in'] = $data->expires_in;
            $result['linkedin_user_name'] = $profile['localizedFirstName'] . " " . $profile['localizedLastName'];
            $result['linkedin_emailAddress'] = '';
            $result['code'] = $code;
        } else {
            $result['error'] = true;
            $result['message'] = $data->error_description;
        }

        return $result;

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
