<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 24/02/2016
 * Time: 15:31
 */

namespace WR\Connector\RSSConnector;


use Cake\ORM\TableRegistry;
use WR\Connector\Connector;
use WR\Connector\IConnector;
use App\Lib\WhiteRabbit\WRClient;
use WR\Connector\ConnectorBean;

class RSSConnector extends Connector implements IConnector
{
    protected $_http;

    private $objectId;
    private $mage;

    function __construct($params)
    {
        $this->_http = new WRClient();
    }

    public function connect($config)
    {
        return "connect";
    }

    public function read($objectId = null)
    {
    }

    public function readPublicPage($objectId = null)
    {
        //debug($objectId); die;
        $rssArray = [];
        if($objectId != null) {
            $content = file_get_contents($objectId); // Feed url

            try {
                $x = new \SimpleXMLElement($content);
                if(isset($x->channel->item))
                    $elementArray = $x->channel->item;
                else
                    $elementArray = $x->entry;

                foreach($elementArray as $entry) {
                    try {
                        $element =  new ConnectorBean();
                        $element->setTitle((string)htmlspecialchars($entry->title));
                        $element->setBody((string)htmlspecialchars($entry->description));
                        $element->setCreationDate((string)$entry->pubDate);
                        $element->setMessageId((string)$entry->guid);
                        $element->setAuthor('');
                        $element->setUri((string)$entry->link);

                        $rssArray[] = $element;
                    } catch (\Exception $e) {
                        continue;
                    }
                }

            } catch (\Exception $e) {
                // Do nothing
            }
        }

        return $rssArray;
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

    public function delete($objectId = null)
    {
    }

    public function mapFormData($data) {
        return $data;
    }

    public function stats($objectId)
    {
    }

    public function comments($objectId)
    {
    }

    public function user($objectId)
    {
    }

    public function add_user($content)
    {
    }

}