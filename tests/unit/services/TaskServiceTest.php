<?php

namespace app\tests\unit\services;


use app\services\SessionService;
use app\services\TaskService;
use Codeception\Test\Unit;
use Yii;

/**
 * 用户登录单元测试
 *
 * @package tests\controllers
 */
class LoginServiceTest extends Unit
{


    protected function setUp()
    {
        return parent::setUp(); // TODO: Change the autogenerated stub
    }

    /**
     * 检测用户新绑数据流程
     */
    public function testAuthorizeDispatchNew()
    {
        $data = [
            "AppId" => "wxebf053990b5bf228",
            "CreateTime" =>"1530951102",
            "InfoType" =>"authorized",
            "AuthorizerAppid" =>"wxc6ca39f6aa6cabfe",
            "AuthorizationCode" => "queryauthcode@@@N-eoWdYUaL_9uXVLgWlUasvqzi54NPUvtt_JlGgGpjz6aN9AqH365sfitdCXj-3dplupAa54wLMPFwSO9M5nEg",
            "AuthorizationCodeExpiredTime" =>"1530954702",
            "PreAuthCode" =>"preauthcode@@@MqVjKcYbVRKb5dDP7mpsUqaWRqZ2LdcwvZQWS3jfRpnOpRL9mXHcQ3iuBcqresLF",

        ];
        $return = (new TaskService)->authorizeDispatch($data);
        $this->assertEquals(true, $return);
    }

    /**
     * 检测用户重新绑定流程
     */
    public function testAuthorizeDispatch()
    {
        $data = [
            "AppId" => "wxebf053990b5bf228",
            "CreateTime" => "1530620196",
            "InfoType" => "updateauthorized",
            "AuthorizerAppid" => "wxc6ca39f6aa6cabfe",
            "AuthorizationCode" => "queryauthcode@@@OK8KF1by-LniFUpZXkhYzU7Fan_aBS_Rge6iTFWb2P-PiwvtsNAHZD-wAevMyzL8dRyUJHLPrrv9saoi1NPLrw",
            "AuthorizationCodeExpiredTime" => "1530623796",
            "PreAuthCode" => "preauthcode@@@4QO4_1SjtKa3IG_S3hzTzaCvBdKzTrpTl1D_4GVyURS9_IaZFRV2c72-uP23RbYv",

        ];
        $return = (new TaskService)->authorizeDispatch($data);
        $this->assertEquals(true, $return);
    }

    /**
     * 检测用户解绑流程
     */
    public function testAuthorizeDispatchDelete()
    {
        $data = [
        "AppId" => "wxebf053990b5bf228",
   	    "CreateTime" =>"1529571561",
  	    "InfoType" =>"unauthorized",
  	    "AuthorizerAppid" =>"wxc6ca39f6aa6cabfe",
        ];
        $return = (new TaskService)->authorizeDispatch($data);
        $this->assertEquals(true, $return);
    }
}