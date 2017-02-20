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