<?php
/**
 * Created by Fabio Mugnano.
 * User: user
 * Date: 19/04/2019
 * Time: 12:06
 */

namespace WR\Connector\ZapierTriggerConnector;


use App\Controller\Api\ZapierController;
use App\Controller\Event\Bean\PreparedContentEventBean;
use WR\Connector\Connector;
use WR\Connector\ConnectorBean;
use WR\Connector\IConnector;
use Cake\Collection\Collection;
use Cake\I18n\Time;
use Cake\Routing\Router;
use App\Controller\Event\WREvents;
use Cake\ORM\TableRegistry;

class ZapierTriggerPostConnecotor extends ZapierTriggerConnector
{

    const CONNECTOR_INSTANCE_CHANNEL = 'connector_instance_channel';
    const CONTENT = 'content';

    public function write($content)
    {
        try {
            $rhTable = TableRegistry::getTableLocator()->get('RestHooksSubscriptions');
            $preparedContentEventBean = new PreparedContentEventBean();
            //$content['content']['main_video']
            //$content['content']['language_code']
            //$content['content']['main_video']
            //$content['content']['post_status']'' => 'publish',
            //$content['content']['post_date_gmt']'' => '',
            //$content['content']['post_date']'' => '',
            if (!$rhTable->isZapierActive($content['customer']['id'], WREvents::PREPARED_CONTENT_CREATED, $content['' . static::CONNECTOR_INSTANCE_CHANNEL . '']['name'])) {
                $value['Error'] = true;
                $value['Message'] = print_r("No zap for " . $content[static::CONNECTOR_INSTANCE_CHANNEL]['name'], true);
                return $value;
            }

            $preparedContentEventBean->setTarget('zapier.' . $content[static::CONNECTOR_INSTANCE_CHANNEL]['name'])
                ->setTitle($content[static::CONTENT]['title'])
                ->setCustomerId($content['customer']['id'])
                ->setAbstract($content[static::CONTENT]['abstract'])
                ->setBody($content[static::CONTENT]['body'])
                ->setMainUrl($content[static::CONTENT]['main_url'])
                ->setMainImage($content[static::CONTENT]['main_image'])
                ->setMetaDescription($content[static::CONTENT]['meta_description'])
                ->setMetaKeywords($content[static::CONTENT]['meta_keywords']);
            $preparedContentEventBean->dispatch();
        } catch (\Throwable $th) {
            \Cake\Log\Log::debug('Zapier share exception: ' . print_r($th->getMessage(), true));
            $value['Error'] = true;
            $value['Message'] = print_r($th->getMessage(), true);
            // $value['Message'] = true
            return $value;
        }

        $value['id'] = null;
        $value['url'] = 'https://zapier.com/app/history';
        $value['post_status'] = 'publish';
        $value['post_date'] = date('Y-m-d H:i:s');
        return $value;

    }


}
