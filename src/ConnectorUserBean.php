<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 08/02/2017
 * Time: 11:41
 */

namespace WR\Connector;

use App\Lib\Bean;
use Cake\I18n\Time;

class ConnectorUserBean extends Bean
{
    private $_name = '';
    private $_id = '';
    private $_action = '';
    private $_content_id = '';
    private $_text = '';
    private $_date = '';

    private $_firstname = '';
    private $_lastname = '';
    private $_gender = '';
    private $_coverimage = '';
    private $_locale = '';
    private $_currency = '';
    private $_devices = '';
    private $_link = '';

    private $_ancestor_body = '';

    /**
     * @return string
     */
    public function getAncestorBody()
    {
        return $this->_ancestor_body;
    }

    /**
     * @param string $ancestor_body
     */
    public function setAncestorBody($ancestor_body)
    {
        $this->_ancestor_body = $ancestor_body;
    }

    /**
     * @return string
     */
    public function getFirstname()
    {
        return $this->_firstname;
    }

    /**
     * @param string $firstname
     */
    public function setFirstname($firstname)
    {
        $this->_firstname = $firstname;
    }

    /**
     * @return string
     */
    public function getLastname()
    {
        return $this->_lastname;
    }

    /**
     * @param string $lastname
     */
    public function setLastname($lastname)
    {
        $this->_lastname = $lastname;
    }

    /**
     * @return string
     */
    public function getGender()
    {
        return $this->_gender;
    }

    /**
     * @param string $gender
     */
    public function setGender($gender)
    {
        $this->_gender = $gender;
    }

    /**
     * @return string
     */
    public function getCoverimage()
    {
        return $this->_coverimage;
    }

    /**
     * @param string $coverimage
     */
    public function setCoverimage($coverimage)
    {
        $this->_coverimage = $coverimage;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->_locale;
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->_locale = $locale;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->_currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->_currency = $currency;
    }

    /**
     * @return string
     */
    public function getDevices()
    {
        return $this->_devices;
    }

    /**
     * @param string $devices
     */
    public function setDevices($devices)
    {
        $this->_devices = $devices;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->_link;
    }

    /**
     * @param string $link
     */
    public function setLink($link)
    {
        $this->_link = $link;
    }


    /**
     * @return string
     */
    public function getDate()
    {
        return $this->_date;
    }

    /**
     * @param string $date
     */
    public function setDate($date)
    {
        $this->_date = $date;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->_text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->_text = $text;
    }

    /**
     * @return string
     */
    public function getContentId()
    {
        return $this->_content_id;
    }

    /**
     * @param string $content_id
     */
    public function setContentId($content_id)
    {
        $this->_content_id = $content_id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->_action;
    }

    /**
     * @param string $action
     */
    public function setAction($action)
    {
        $this->_action = $action;
    }

}