<?php
namespace WR\Connector\Controller;
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 29/03/2016
 * Time: 09:39
 */
class ConnectorApiController extends AppController
{

    public function apiConnect () {
        /*
        {
            "connector":"MagentoConnector",
            "uri":"",
            "name":"",
            "user":"",
            "password":"",
        }
        */
        $this->viewBuilder()->layout('ajax');
        $data = $this->request->data();

        //TODO: ...
        $connectorChannelId = 4; // ID connettore Magento
        $connectorInstanceId = 2; // ID attuale istanza cliente1 connettore Magento

        $token = Text::uuid();
        $data['token'] = $token;

        $res = [];
        $res['token'] = $token;

        $this->loadModel('ConnectorInstanceChannelSettings');
        $this->loadModel('ConnectorChannelSettings');

        $channelPresent = false;
        try {
            $uriSetting =  $this->ConnectorChannelSettings->find('all')
                ->where(['ConnectorChannelSettings.name' => 'uri'])
                ->where(['ConnectorChannelSettings.connector_channel_id' => $connectorChannelId])
                ->first();

            $connectorInstanceChannelSetting =  $this->ConnectorInstanceChannelSettings->find('all')
                ->where(['ConnectorInstanceChannelSettings.connector_channel_setting_id' => $uriSetting->id])
                ->where(['ConnectorInstanceChannelSettings.value' => $data['uri']])
                ->first();

            $channelPresent = $connectorInstanceChannelSetting != null;
            if ($channelPresent) {
                $res['id'] = $connectorInstanceChannelSetting->connector_instance_channel_id;
            }
        } catch (Exception $e) {
            $channelPresent = false;
        }


        if(!$channelPresent) {
            $connectorInstanceChannel = $this->ConnectorInstanceChannels->newEntity();
            $connectorInstanceChannel = $this->ConnectorInstanceChannels->patchEntity($connectorInstanceChannel, $data);
            $connectorInstanceChannel->connector_instance_id = $connectorInstanceId;
            $connectorInstanceChannel->connector_channel_id = $connectorChannelId;

            // Saving channel settings
            $instanceFields = ['name', 'connector_channel_id', 'connector_instance_id'];
            $connectorInstanceChannelSettings = [];
            foreach ($data as $key => $value) {
                if (!in_array($key, $instanceFields)) {
                    $connectorChannelSettings =  $this->ConnectorChannelSettings->find('all')
                        ->where(['ConnectorChannelSettings.name' => $key])
                        ->where(['ConnectorChannelSettings.connector_channel_id' => $connectorInstanceChannel->connector_channel_id])
                        ->first();

                    if ($connectorChannelSettings != null) {
                        $connectorInstanceChannelSetting = $this->ConnectorInstanceChannels->ConnectorInstanceChannelSettings->newEntity();
                        $connectorInstanceChannelSetting->value = $value;
                        $connectorInstanceChannelSetting->connector_channel_setting_id = $connectorChannelSettings->id;
                        $connectorInstanceChannelSettings[] = $connectorInstanceChannelSetting;
                    }
                }
            }
            $connectorInstanceChannel->connector_instance_channel_settings = $connectorInstanceChannelSettings;

            if ($this->ConnectorInstanceChannels->save($connectorInstanceChannel)) {
                $channelPresent = true;
                $res['id'] = $connectorInstanceChannel->id;
            } else {
                $channelPresent = false;
            }
        }

        $res['error'] = !$channelPresent;
        header('Content-Type: application/json');
        echo json_encode($res);
    }

    public function apiGetInfo () {
        /*
        {
            "connector":"MagentoConnector",
            "token":""
        }
        */
        $this->viewBuilder()->layout('ajax');
        $data = $this->request->data();

        //TODO: ...
        $connectorChannelId = 4; // ID connettore Magento
        $connectorInstanceId = 2; // ID attuale istanza cliente1 connettore Magento

        // Verifica token
        $res = [];
        if ($this->verifyToken($connectorChannelId, $data['token'])) {

            $res['error'] = false;
        } else {
            $res['error'] = true;
        }

        header('Content-Type: application/json');
        echo json_encode($res);
    }


    /**
     * @param $connectorChannelId
     * @param $token
     * @return bool
     */
    private function verifyToken ($connectorChannelId, $token) {
        $this->loadModel('ConnectorInstanceChannelSettings');
        $this->loadModel('ConnectorChannelSettings');

        try {
            $uriSetting =  $this->ConnectorChannelSettings->find('all')
                ->where(['ConnectorChannelSettings.name' => 'token'])
                ->where(['ConnectorChannelSettings.connector_channel_id' => $connectorChannelId])
                ->first();

            $connectorInstanceChannelSetting =  $this->ConnectorInstanceChannelSettings->find('all')
                ->where(['ConnectorInstanceChannelSettings.connector_channel_setting_id' => $uriSetting->id])
                ->where(['ConnectorInstanceChannelSettings.value' => $token])
                ->first();

            if ($connectorInstanceChannelSetting != null) {
                // Token ok
                return true;
            } else {
                // Token error
                return false;
            }
        } catch (Exception $e) {
            // Token error
            return false;
        }
    }

}