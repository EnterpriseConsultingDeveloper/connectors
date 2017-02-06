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
        $content = file_get_contents($objectId); // Feed url
        $x = new \SimpleXMLElement($content);

        $rssRow = [];
        foreach($x->channel->item as $entry) {
            //debug($entry); die;
            $rssRow['link'] = (string)$entry->link;
            $rssRow['title'] = (string)$entry->title;
            $rssRow['pubDate'] = (string)$entry->pubDate;
            $rssRow['description'] = (string)$entry->description;
            $rssRow['category '] = (string)$entry->category;
            $rssRow['guid'] = (string)$entry->guid;
            $rssArray[] = $rssRow;
        }

        return $this->format_result($rssArray);
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

    private function format_result($posts) {
        //debug($posts); die;
        $beans = array();
        foreach($posts as $key => $value) {
            $element =  new ConnectorBean();
            $element->setTitle($posts[$key]['title']);
            $element->setBody($posts[$key]['description']);
            $element->setCreationDate($posts[$key]['pubDate']);
            $element->setMessageId($posts[$key]['guid']);
            $element->setAuthor('');
            $element->setUri($posts[$key]['link']);

            $element->setRawPost($posts[$key]);

            $beans[] = $element;
        }
        //debug($beans); die;
        return $beans;
    }

}