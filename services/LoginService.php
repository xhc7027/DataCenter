<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/24 0024
 * Time: 19:15
 */

namespace app\services;

use app\models\Login;
use app\models\dao\LoginDAO;
use Yii;
use Idouzi\Commons\StringUtil;
use Idouzi\Commons\SecurityUtil;
use Idouzi\Commons\HttpUtil;
use Idouzi\Commons\Models\RespMsg;
use app\exceptions\SystemException;

class LoginService
{
    public $username;
    public $password;
    public $reserved_phone;

    public static function loginValidate($data)
    {

        $model = new Login();
        $model->username = $data['username'];
        $model->password = $data['password'];
        //model校验
        if (!$model->load($data, '') || !$model->validate()) {
            throw new SystemException(current($model->getFirstErrors()));
        }
        $return = LoginDAO::findAdmin($data);
        if ($return['total'] > 0) {
            self::handleSendMobileCode($return['reserved_phone'], $return['nickname']);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 清空当前用户登录标识
     *
     * @param string $ticket 票据
     * @return bool
     * @throws SystemException
     */
    public function clearUserLoginFlag()
    {
        //清楚所有session信息
       Yii::$app->session->destroy();
            return true;
    }

    /**
     * 发送验证码的业务逻辑
     *
     * @param $phoneNumber
     * @param $nickname
     * @param null $type
     * @return bool
     * @throws SystemException
     * @internal param $phone
     */
    public static function handleSendMobileCode($phoneNumber,$nickname, $type = null)
    {


        $sendCodeTime = Yii::$app->session->get(Yii::$app->params['session']['mobileCodeValidateTime']);
        if ($sendCodeTime != "" && (time() < $sendCodeTime + (int)Yii::$app->params['sendCodeValidate'] * 60)) {
            throw new SystemException('短信发送间隔为' . Yii::$app->params['sendCodeValidate'] . '分钟，请稍后再试！');
            return false;
        }
        //发送验证码
        $code = self::sendMobile($phoneNumber);
        //存入session
        Yii::$app->session->set(Yii::$app->params['session']['mobileCode'], $code);
        //存入用户名
        Yii::$app->session->set(Yii::$app->params['session']['nickName'], $nickname);
        // 存入手机验证码的时间
        Yii::$app->session->set(Yii::$app->params['session']['mobileCodeValidateTime'], time());
        //手机号存入session中
        Yii::$app->session->set(Yii::$app->params['session']['mobileNumber'], $phoneNumber);

        return true;
    }

    /**
     * 请求爱豆子接口发送验证码
     *
     * @param $phoneNumber
     * @return bool
     * @throws SystemException
     * @internal param $phone
     */
    public static function sendMobile($phoneNumber)
    {
        $get = ['timestamp' => time(), 'state' => StringUtil::genRandomStr()];//拼装get参数
        $post = ['phoneNumber' => $phoneNumber];
        //从爱豆子拉取数据回来
        $url = Yii::$app->params['domains']['idouzi'] . '/supplier/api/SendMobile?';
        $get['sign'] = (new SecurityUtil($get, Yii::$app->params['publicKeys']['idouzi']))->generateSign();
        $url .= http_build_query($get);
        $resp = json_decode(HttpUtil::simplePost($url, $post), true);

        if (isset($resp['return_code']) && $resp['return_code'] === RespMsg::SUCCESS) {
            return $resp['return_msg'];
        }
        Yii::warning('发送短信失败' . json_encode($resp));
        throw new SystemException('发送短信失败');
    }


    /**
     * 校验手机验证码
     * @param $code
     * @return bool|mixed
     */
    public static function validatePhone($code)
    {
        $respcode = Yii::$app->session->get(Yii::$app->params['session']['mobileCode']);
        if ($respcode == $code) {
            return Yii::$app->session->get(Yii::$app->params['session']['nickName']);
        }
        return false;
    }
}