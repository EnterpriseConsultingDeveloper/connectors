<?php
/**
 * Created by Dino Fratelli.
 * User: user
 * Date: 02/02/2017
 * Time: 11:54
 */

namespace WR\Connector;

use App\Lib\Bean;
use Cake\I18n\Time;

class ConnectorBean extends Bean
{
    private $_title = '';
    private $_body = '';
    private $_creation_date;
    private $_author = '';
    private $_uri = '';
    private $_iso_language_code = '';
    private $_message_id = '';
    private $_hash = '';

    /**
     * @return string
     */
    public function getTitle()
    {
        if(empty($this->_title)) {
            $max_len = min(80, strlen($this->_body));
            $pos = strpos($this->_body, ' ', $max_len);
            $title = substr($this->_body, 0, $pos);
            if(strlen($title) < strlen($this->_body))
                $title .= '...';

            $this->_title = $title;
        }
        return $this->_title;
    }


    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $v = trim($title);
        $this->_title = $v;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->_body;
    }

    /**
     * @param string $body
     */
    public function setBody($body)
    {
        $this->_body = $body;
    }

    /**
     * @return mixed
     */
    public function getCreationDate()
    {
        return $this->_creation_date;
    }

    /**
     * @param mixed $creation_date
     */
    public function setCreationDate($creation_date)
    {
        $time = new Time($creation_date);
        $time = ($time->i18nFormat('YYYY-MM-dd HH:mm:ss'));

        $this->_creation_date = $time;
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->_author;
    }

    /**
     * @param string $author
     */
    public function setAuthor($author)
    {
        $this->_author = $author;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->_uri;
    }

    /**
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->_uri = $uri;
    }

    /**
     * @return string
     */
    public function getIsoLanguageCode()
    {
        return $this->_iso_language_code;
    }

    /**
     * @param string $iso_language_code
     */
    public function setIsoLanguageCode($iso_language_code)
    {
        $this->_iso_language_code = $iso_language_code;
    }

    /**
     * @return string
     */
    public function getMessageId()
    {
        return $this->_message_id;
    }

    /**
     * @param string $message_id
     */
    public function setMessageId($message_id)
    {
        $this->_message_id = $message_id;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        if(empty($this->_hash))
            $this->_hash = md5($this->_body);

        return $this->_hash;
    }

}