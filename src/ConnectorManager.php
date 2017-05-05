<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 18:36
 */

namespace WR\Connector;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;


class ConnectorManager
{
    private $myConnector;
    private $myClass;

    public function __construct($connector, $class)
    {
        $this->myConnector = $connector;
        $this->myClass = $class;
    }

    /**
     *
     */
    public function install()
    {
        /*
         * 0. Check if connector exists
         * 1. Insert a record in Connectors
         * 2. Insert a record in ConnectorChannels
         * 3. Insert a record in ConnectorChannelSettings
         * 4. Insert a record in ConnectorChannelStreams
         */
        $install_file = file_get_contents($this->myConnector . DS . 'install.json', true);
        $config = json_decode($install_file, true);

        $connectorsTable = TableRegistry::get('Connectors');
        $connector = $connectorsTable->newEntity();

        $connector->name = $config['name'];
        $connector->description = $config['description'];
        $connector->icon = $config['icon'];

        $channels = $config['channels'];
        $connectorChannels = [];
        foreach ($channels as $channel) {
            $connectorChannel = $connectorsTable->ConnectorChannels->newEntity();
            $connectorChannel->name = $channel['name'];
            $connectorChannel->default_channel = $channel['default_channel'];
            $connectorChannels[] = $connectorChannel;

            $streams = $channel['streams'];
            $connectorChannelStreams = [];
            foreach ($streams as $stream) {
                $connectorChannelStream =
                    $connectorsTable->ConnectorChannels->ConnectorChannelStreams->newEntity();
                $connectorChannelStream->name = $stream['name'];
                $connectorChannelStream->permitted_operations = $stream['permitted_operations'];
                $connectorChannelStream->connection_string = $stream['connection_string'];
                $connectorChannelStream->type = $stream['type'];
                $connectorChannelStreams[] = $connectorChannelStream;
            }

            $connectorChannel->connector_channel_streams = $connectorChannelStreams;


            $settings = $channel['settings'];
            $connectorChannelSettings = [];
            foreach ($settings as $setting) {
                $connectorChannelSetting =
                    $connectorsTable->ConnectorChannels->ConnectorChannelSettings->newEntity();
                $connectorChannelSetting->name = $setting['name'];
                $connectorChannelSetting->type = $setting['type'];
                $connectorChannelSetting->is_editable = $setting['is_editable'];
                $connectorChannelSetting->is_mandatory = $setting['is_mandatory'];
                $connectorChannelSetting->access_level = $setting['access_level'];
                $connectorChannelSettings[] = $connectorChannelSetting;
            }

            $connectorChannel->connector_channel_settings = $connectorChannelSettings;
        }

        $connector->connector_channels = $connectorChannels;

        if ($connectorsTable->save($connector)) {


            //TODO: Creare anche il necessario rabbit_source dalle source che devono essere comunque presenti prima
            /*$rabbit_id = $this->request->session()->read('Config.Rabbit_id');
        $sourceList = $this->SourceLists->newEntity();
        if ($this->request->is('post')) {
            if (isset($this->request->data['url'])) {
                $sourceList = $this->SourceLists->patchEntity($sourceList, $this->request->data, ['validate' => 'url']);
            } else {
                $sourceList = $this->SourceLists->patchEntity($sourceList, $this->request->data, ['validate' => 'name']);
            }
            $sources = $this->SourceLists->RabbitSources->find('')
                ->select(['Sources.id', 'Sources.name'])
                ->contain(['Sources'])
                ->where(['RabbitSources.id=' . $this->request->data['rabbit_source_id']])->first();
            $this->loadModel('RabbitSourceSettings');
            $settings = $this->RabbitSourceSettings->find()
                ->contain(['SourceSettings', 'RabbitSources'])
                ->where(['SourceSettings.source_id' => $sources->Sources->id])
                ->where(['RabbitSources.rabbit_id' => $rabbit_id]);

            $params = array();
            foreach ($settings as $id => $setting) {
                $params[$setting->source_setting->name] = $setting->value;
            }

            if (isset($this->request->data['url'])) {
                $params['url'] = $this->request->data['url'];
            }
            $socialGateway = new SocialGateway();

            $data = $socialGateway->getPageParameters($sources->Sources->name, $params);

            if ($data['error'] != Null) {
                $this->Flash->error($data['error']);
                return $this->redirect(['action' => 'add']);
            }

            $sourceList = $this->SourceLists->patchEntity($sourceList, $data);
            if ($this->SourceLists->save($sourceList)) {

                $data = $socialGateway->activateCron($sources->Sources->name, $params);

                if (isset($data['cron'])) {
                    $this->Flash->success('The source list has been saved. You\'ll see the first ' . $sources->Sources->name . '\'s content from ' . $data['cron']);
                } else {
                    $this->Flash->success('The source list has been saved.');
                }

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error('The source list could not be saved. Please, try again.');
            }
        }
        $rabbitSources = $this->SourceLists->RabbitSources->find()
            ->select(['id', 'Sources.name'])
            ->contain(['Sources'])
            ->where(['rabbit_id' => $rabbit_id]);

        if (empty($rabbitSources) || empty($rabbitSources->toArray())) {
            $this->Flash->warning('You must add one rabbit source.');
            return $this->redirect(['controller' => 'RabbitSources', 'action' => 'add']);
        }

        $data = array();
        foreach ($rabbitSources as $id => $rabbitSource) {
            $data[$rabbitSource->id] = $rabbitSource->source->name;
        }

        $this->loadModel('LanguagesRabbits');
        $rabbit_id = $this->request->session()->read('Config.Rabbit_id');
        $languages_list = $this->LanguagesRabbits->find()
            ->contain(['Languages'])
            ->select(['Languages.id', 'Languages.name'], true)
            ->where(['LanguagesRabbits.rabbit_id' => $rabbit_id]);

        $languages = array();
        foreach ($languages_list as $lang) {
            $languages[$lang->Languages->id] = $lang->Languages->name;
        }

        $this->set(compact('sourceList', 'rabbitSources', 'languages', 'data'));
        $this->set('_serialize', ['sourceList']);
        $this->set('_serialize', ['data']);*/

            $id = $connector->id;
            //debug($id);
        } else {
            debug('Connettore gi? presente o errore nei dati'); die;
        }
    }


    /**
     * @return mixed
     */
    public function connectorInstance()
    {
        $className = 'WR\\Connector\\' . $this->myConnector . '\\' . ucfirst($this->myClass);
        return new $className();
    }

    /**
     * @param $params
     * @param $objectId
     * @return mixed
     */
    public function delete_content($params, $objectId)
    {
        $className = 'WR\\Connector\\' . $this->myConnector . '\\' . ucfirst($this->myClass);
        $classInstance = new $className($params);
        return $classInstance->delete($objectId);
    }

    /**
     * @param $params
     * @param $objectId
     * @return mixed
     */
    public function get_content($params, $objectId)
    {
        $className = 'WR\\Connector\\' . $this->myConnector . '\\' . ucfirst($this->myClass);
        $classInstance = new $className($params);
        return $classInstance->read($objectId);
    }

    /**
     * @param $params, just 'feedLimit'
     * @param $objectId
     * @return mixed
     */
    public function get_public_content($params, $objectId)
    {
        $className = 'WR\\Connector\\' . $this->myConnector . '\\' . ucfirst($this->myClass);
        $classInstance = new $className($params);
        return $classInstance->readPublicPage($objectId);
    }

    /**
     * @param $params
     * @param $objectId
     * @return mixed
     */
    public function get_fan($params, $objectId)
    {
        $className = 'WR\\Connector\\' . $this->myConnector . '\\' . ucfirst($this->myClass);
        $classInstance = new $className($params);
        return $classInstance->captureFan($objectId);
    }

    /*
     * function update_content
     *
     * Gateway for update content to social and sons
     */
    public function update_content($params, $content, $objetcId)
    {
        $className = 'WR\\Connector\\' . $this->myConnector . '\\' . ucfirst($this->myClass);
        $classInstance = new $className($params);
        return $classInstance->update($content, $objetcId);
    }

    /*
     * function send_content
     *
     * Gateway for sending content to social and sons
     */
    public function send_content($params, $content)
    {
        $className = 'WR\\Connector\\' . $this->myConnector . '\\' . ucfirst($this->myClass);
        $classInstance = new $className($params);
        return $classInstance->write($content);
    }

    /**
     * @param $params
     * @param $objectId
     * @return mixed
     */
    public function get_stats($params, $objectId)
    {
        $className = 'WR\\Connector\\' . $this->myConnector . '\\' . ucfirst($this->myClass);
        $classInstance = new $className($params);
        return $classInstance->stats($objectId);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function mapFormData($data)
    {
        $className = 'WR\\Connector\\' . $this->myConnector . '\\' . ucfirst($this->myClass);
        $classInstance = new $className(null);
        return $classInstance->mapFormData($data);
    }

    /**
     * @param $params
     * @param $objectId
     * @return mixed
     */
    public function get_comments($params, $objectId)
    {
        $className = 'WR\\Connector\\' . $this->myConnector . '\\' . ucfirst($this->myClass);
        $classInstance = new $className($params);
        return $classInstance->comments($objectId, 'r');
    }

    /**
     * @param $params
     * @param $objectId
     * @return mixed
     */
    public function send_comments($params, $objectId, $content)
    {
        $className = 'WR\\Connector\\' . $this->myConnector . '\\' . ucfirst($this->myClass);
        $classInstance = new $className($params);
        return $classInstance->comments($objectId, 'w', $content);
    }

    /**
     * @param $params
     * @param $objectId
     * @return mixed
     */
    public function update_comment($params, $objectId, $content)
    {
        $className = 'WR\\Connector\\' . $this->myConnector . '\\' . ucfirst($this->myClass);
        $classInstance = new $className($params);
        return $classInstance->comments($objectId, 'u', $content);
    }


    /**
     * @param $params
     * @param $objectId
     * @return mixed
     */
    public function delete_comment($params, $objectId)
    {
        $className = 'WR\\Connector\\' . $this->myConnector . '\\' . ucfirst($this->myClass);
        $classInstance = new $className($params);
        return $classInstance->comments($objectId, 'd');
    }

    /**
     * @param $params
     * @param $objectId
     * @return mixed
     */
    public function get_user($params, $objectId)
    {
        $className = 'WR\\Connector\\' . $this->myConnector . '\\' . ucfirst($this->myClass);
        $classInstance = new $className($params);
        return $classInstance->user($objectId);
    }


    /**
     * @param $operation
     * @param $params
     * @param $content
     * @return mixed
     */
    public function set_event($operation, $params, $content)
    {
        try {
            $connectorLogs = TableRegistry::get('ConnectorLogs');
            $connectorLog = $connectorLogs->newEntity();
            $connectorLog->connector = $this->myConnector . '.' . ucfirst($this->myClass);
            $connectorLog->directory_id = 1;


            if(isset($content['customer_id']))
                $connectorLog->customer_id = $content['customer_id'];


            if(isset($content['directory_id']))
                $connectorLog->directory_id = $content['directory_id'];

            if(is_array($content))
                $connectorLog->mydata = implode(",", $content);
            else
                $connectorLog->mydata = $content;

            $connectorLogs->save($connectorLog);
        } catch (Exception $e) {

        }

        $className = 'WR\\Connector\\' . $this->myConnector . '\\' . ucfirst($this->myClass);
        $classInstance = new $className($params);
        return $classInstance->$operation($content);
    }


    /**
     * @param $params
     * @param $objectId
     * @return mixed
     */
    public function get_comments_from_date($params, $objectId)
    {
        $className = 'WR\\Connector\\' . $this->myConnector . '\\' . ucfirst($this->myClass);
        $classInstance = new $className($params);
        return $classInstance->commentFromDate($objectId);
    }


    /**
     * @param $params
     * @param $objectId
     * @return mixed
     */
    public function connect($params)
    {
        $className = 'WR\\Connector\\' . $this->myConnector . '\\' . ucfirst($this->myClass);
        $classInstance = new $className($params);
        return $classInstance->connect();
    }

    /*
     * Test method, useless
     */
    public function create_connector($name)
    {
        if (array_key_exists($name, $this->myConnector)) {
            $connector = $this->connectors[$name];
            $className = 'WR\\Connector\\' . ucfirst($connector);

            return new $className($name);
        }

        return new DefaultConnector($name);
    }
}
