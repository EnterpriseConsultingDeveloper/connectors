<?php

namespace WR\Connector\FacebookConnector;


use App\Controller\Component\UtilitiesComponent;
use Cake\I18n\Time;
use Cake\Utility\Hash;
use Facebook\Facebook;
use WR\Connector\Connector;
use WR\Connector\FacebookConnector\FacebookConnector;
use WR\Connector\IConnector;
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;

class FacebookAdsConnector extends FacebookConnector
{

    public function __construct($params)
    {
        parent::__construct($params);
    }

    public function write($content)
    {
        return null;
    }

    public function update($content, $objectId)
    {
        return null;
    }

    public function read($objectId = null)
    {
        // Read only campaigns
        if ($objectId == null)
            $objectId = $this->objectAdsId;

        if ($objectId == null) {
            return [];
        }

        $streamToRead = '/' . $objectId . '?fields=campaigns{status,name,created_time},name,account_status';

        try {
            $request = $this->fb->request('GET', $streamToRead);
            $graphNode = $this->fb->getClient()->sendRequest($request);
            $graphEdge = $graphNode->getGraphNode()->getField('campaigns');

            $accountName = $graphNode->getGraphNode()->getField('name');
            $accountStatus = $graphNode->getGraphNode()->getField('account_status');
            $accountId = $graphNode->getGraphNode()->getField('id');

            $result = [];
            $adsArray['account_id'] = $accountId;
            $adsArray['account_name'] = $accountName;
            $adsArray['account_status'] = $accountStatus;

            //TODO: if isset campaigns
            if ($this->fb->next($graphEdge)) {
                $campaignsArray = $graphEdge->asArray();
                $result += $campaignsArray;

                while ($graphEdge = $this->fb->next($graphEdge)) {
                    $campaignsArray = $graphEdge->asArray();
                    $result = array_merge($result, $campaignsArray);
                }

            } else {
                $campaignsArray = $graphEdge->asArray();
                $result += $campaignsArray;
            }


            foreach($result as $key => $elem) {
                $val = Hash::extract($result, $key. '.status');
                $result = Hash::remove($result, $key.'.status');
                $result = Hash::insert($result, $key.'.campaign_status', $val[0]);

                $val = Hash::extract($result, $key.'.id');
                $result = Hash::remove($result, $key.'.id');
                $result = Hash::insert($result, $key.'.campaign_id', $val[0]);

                $val = Hash::extract($result, $key.'.name');
                $result = Hash::remove($result, $key.'.name');
                $result = Hash::insert($result, $key.'.campaign_name', $val[0]);

                $val = Hash::extract($result, $key.'.created_time');
                $result = Hash::remove($result, $key.'.created_time');
                $result = Hash::insert($result, $key.'.campaign_date', $val[0]);

                $result[$key] += $adsArray;
            }

        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $result = [];
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * @param null $objectId
     * @return array
     */
    public function captureFan($objectId = null) {
        // Read complete lead gen
        if ($objectId == null)
            $objectId = $this->objectAdsId;

        if ($objectId == null) {
            return [];
        }

        $forms_lang = null;
        $forms_lang = Config::load( __DIR__ . '/lang', new Json)->all();
        $iso_lang = array_keys($forms_lang);

        try {

            $streamToRead = '/' . $objectId . '?fields=campaigns{adsets{ads{status,leads{field_data,form_id,platform,ad_id,ad_name,adset_id,adset_name,campaign_id,campaign_name,created_time}},status}},account_status';
            $response = $this->fb->sendRequest('GET', $streamToRead);

            $data = $response->getDecodedBody()['campaigns'];

            // Append users that have taken lead gen
            $social_users = array();
            $dataCrm = array();
            $form_ids = array();

            foreach ($data['data'] as $d) {
                if (isset($d['adsets'])) {
                    foreach ($d['adsets']['data'] as $ad_set) {
                        if (isset($ad_set['ads'])) {
                            foreach ($ad_set['ads']['data'] as $ads) {
                                if (isset($ads['leads'])) {
                                    foreach ($ads['leads']['data'] as $key => $leads) {
                                        if (!in_array($leads['form_id'],array_keys($form_ids))) {
                                            //GET locale of Forms
                                            $streamToRead = '/' . $leads['form_id'] . '?fields=locale';
                                            $response = $this->fb->sendRequest('GET', $streamToRead);
                                            //check if exist lang on folder
                                            if(!isset($forms_lang) || !in_array($response->getDecodedBody()['locale'],$iso_lang)){
                                                continue;
                                            }
                                            $fields_lang = $forms_lang[$response->getDecodedBody()['locale']];

                                            $form_ids[$leads['form_id']]['locale'] = $response->getDecodedBody()['locale'];
                                            $form_ids[$leads['form_id']]['field_maps'] = $fields_lang;
                                        }

                                        foreach ($leads['field_data'] as $form_field){
                                            $social_users[$form_field['name']] = $form_field['values'][0];
                                        }

                                        //Mapping Field Form and CRM for all Lang
                                        $dataCrm[$key] = UtilitiesComponent::remap($social_users, $form_ids[$leads['form_id']]['field_maps'], false);
                                        $dataCrm[$key] += [
                                            "date_add" => $leads['created_time'],
                                            "action" => "lead",
                                            "contentId" => $leads['ad_id'],
                                            "platform" => $leads['platform'],
                                            "ad_name" => $leads['ad_name'],
                                            "id" => $leads['id'],
                                            "adset_name" => $leads['adset_name'],
                                            "campaign_name" => $leads['campaign_name'],
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $data['social_users'] = $dataCrm;
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            $data = [];
            $data['error'] = $e->getMessage();
        }

        return ($data);
    }

}