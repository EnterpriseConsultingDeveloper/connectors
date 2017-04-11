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
    private $_raw_post = '';
    private $_is_content_meaningful = 1;

    /**
     * @return int
     */
    public function getIsContentMeaningful()
    {
        return $this->_is_content_meaningful;
    }

    /**
     * @param int $is_content_meaningful
     */
    public function setIsContentMeaningful($is_content_meaningful)
    {
        $this->_is_content_meaningful = $is_content_meaningful;
    }

    /**
     * @return string
     */
    public function getRawPost()
    {
        return $this->_raw_post;
    }

    /**
     * @param string $raw_post
     */
    public function setRawPost($raw_post)
    {
        $this->_raw_post = $raw_post;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        if(empty($this->_title)) {
            $max_len = 80;
            if(strlen($this->_body) > $max_len) {
                $pos = strpos($this->_body, ' ', $max_len);
                $pos = ($pos == 0) ? $max_len : $pos;
                $this->_title = substr($this->_body, 0, $pos) . '...';
            } else {
                $this->_title = $this->_body;
            }
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

      // se è nulla imposta una data fissa perchè nel db non è possibile salvare campo vuoto
      if ($creation_date == null) {
        $time = '1999-12-31 23:59:59';
      } else {
        $time = new Time($creation_date);
        $time = $time = $time->toAtomString();
      }

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

    public function hashcode() {
        $arr = ['title' => $this->getTitle(), 'body' => $this->getBody()];
        return md5(json_encode($arr));
    }

}
