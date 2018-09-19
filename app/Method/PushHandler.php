<?php
/**
 * Created by PhpStorm.
 * User: liqihai
 * Date: 16/4/21
 * Time: 15:26
 */

namespace App\Method;


use Curl\Curl;
use Illuminate\Support\Facades\Log;

class PushHandler
{
    /** @var string */
    protected $appId;

    /** @var string */
    protected $appApiToken;

    /** @var string */
    protected $ionicPushEndPoint;

    /**
     * PushHandler constructor.
     * @param $appId
     * @param $appApiToken
     * @param string $ionicPushEndPoint
     */
    public function __construct(
        $appId,
        $appApiToken,
        $ionicPushEndPoint = 'https://onesignal.com/api/v1/notifications'
    )
    {
        $this->appId = $appId;
        $this->appApiToken = $appApiToken;
        $this->ionicPushEndPoint = $ionicPushEndPoint;
    }

    public function getAppId()
    {
        return $this->appId;
    }

    public function getAppApiToken()
    {
        return $this->appApiToken;
    }

    public function getPushEndPoint()
    {
        return $this->ionicPushEndPoint;
    }

    public function setPushEndPoint($ionicPushEndpoint)
    {
        $this->ionicPushEndPoint = $ionicPushEndpoint;
    }

    /**
     * @param array $devices
     * @param array $notification
     */
    public function notify(array $devices, array $notification)
    {
        $body = $this->getNotificationBody($devices, $notification);
        $this->sendRequest($body);
    }


    /**
     * @param array $devices
     * @param array $notification
     *
     * @return string
     */
    protected function getNotificationBody(array $devices, array $notification)
    {
        $body = array(
            'include_player_ids' => $devices,
            'app_id' => $this->appId,
            'data' => array("foo" => "bar"),
            'contents' => $notification
        );

        return json_encode($body);
    }

    /**
     * @param $body
     */
    protected function sendRequest($body)
    {
        $curl = new Curl();
        $curl->setHeader('Authorization' ,sprintf("Basic %s", $this->appApiToken));
        $curl->setHeader('Content-Type' , 'application/json; charset=utf-8');
        $curl->post($this->ionicPushEndPoint,$body);
        if ($curl->error) {
            Log::info('Error: ' . $curl->errorCode . ': ' . $curl->errorMessage);
        }
        else {
            Log::info(json_encode( $curl->response));
        }
    }
}