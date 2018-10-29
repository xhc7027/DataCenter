<?php

namespace app\commons;

use app\models\RespMsg;
use Curl\Curl;
use yii;

/**
 * 网络处理工具类
 *
 * @package app\commons
 */
class HttpUtil
{
    /**
     * 以POST方式发送请求
     *
     * @param string $url 请求链接
     * @param array $params 参数
     * @return string 返回响应数据，以字符串形式
     * @throws HttpException 请求数据出错时抛出异常
     */
    public static function simplePost(string $url, array $params): string
    {
        $curl = new Curl();
        $curl->post($url, $params);
        $curl->close();

        if ($curl->error) {
            Yii::error('发送数据:' . json_encode($params) . '到:' . $url . '错误', __METHOD__);
            throw new \Exception("请求外部接口网络异常");
        }

        return $curl->response;
    }

    /**
     * 发送一个GET请求
     * @param $url
     * @param $params
     * @return RespMsg
     */
    public static function get($url, $params = null)
    {
        return self::http($url, 'GET', $params, null);
    }

    /**
     * 发送一个POST请求
     * @param $url
     * @param $params
     * @param $header
     * @return RespMsg
     */
    public static function post($url, $params = null, $header = null)
    {
        return self::http($url, 'POST', $params, $header);
    }

    /**
     * 发送一个HTTP请求<br>
     * 向指定的链接发送一个HTTP请求
     * @param string $url 被请求链接
     * @param string $method 请求类型，默认“GET”
     * @param string $params 请求附加参数，支持数组或字符
     * @param array|null $header 请求附加参数，数组
     * @return RespMsg 返回响应内容
     */
    public static function http($url, $method, $params, $header = null)
    {
        $curl = new Curl();
        if ($header) {
            if (is_array($header)) {
                foreach ($header as $key => $value) {
                    $curl->setHeader($key, $value);
                }
            }
        }
        if ('POST' === $method) {
            $curl->post($url, $params);
        } elseif ('GET' === $method) {
            $requestUrl = $params ? $url . '?' . $params : $url;
            $curl->get($requestUrl);
        }

        $respMsg = new RespMsg();
        //判断请求状态
        if ($curl->error) {
            Yii::warning('请求错误：' . $url . ', ' . 'errorMsg: ' . $curl->http_error_message, __METHOD__);
            $respMsg->return_code = RespMsg::FAIL;
            $respMsg->return_msg = '请求对方服务异常';
        } else {
            //判断业务处理状态
            $response = json_decode($curl->response);
            if (!$response || (isset($response->errcode) && $response->errcode !== 0)) {
                Yii::error(!$response . '调用接口：' . $url . '，参数：' . json_encode($header) . '，返回不正常信息：' . $curl->response, __METHOD__);
                $respMsg->return_code = RespMsg::FAIL;
            }
            $respMsg->return_msg = $response;
        }
        $curl->close();

        //返回请求结果
        return $respMsg;
    }

    /**
     * 判断用户请求是否来源于移动设备
     *
     * @return bool 如果返回true则表示当前请求来源于移动设备
     */
    public static function isPhone(): bool
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])
            && preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|mobile)/i',
                strtolower($_SERVER['HTTP_USER_AGENT']))
        ) {
            return true;
        }

        if ((strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') > 0)
            or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))
        ) {
            return true;
        }

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
            $mobile_agents = array(
                'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
                'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
                'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
                'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
                'newt', 'noki', 'oper', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox',
                'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar',
                'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-',
                'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp',
                'wapr', 'webc', 'winw', 'winw', 'xda', 'xda-', 'Googlebot-Mobile');

            if (in_array($mobile_ua, $mobile_agents)) {
                return true;
            }
        }

        if (isset($_SERVER['ALL_HTTP'])
            && strpos(strtolower($_SERVER['ALL_HTTP']), 'OperaMini') > 0
        ) {
            return true;
        }

        return false;
    }

    /**
     * 提供一个静态方法去对get或者post输入进行xss过滤
     */
    public static function filterParam()
    {
        $get = Yii::$app->request->get();
        $post = Yii::$app->request->post();
        $_GET = $get ? self::pregParam($get) : [];
        $_POST = $post ? self::pregParam($post) : [];
    }

    /**
     * @param array $request 用户提供过来的数据
     * @return array|mixed 返回过滤后的数据
     */
    public static function pregParam($request)
    {
        $preg = "/<\s*\/*\s*script.*(<\s*\/\s*script\s*>|(\/|\s*>))/is";//去掉script标签
        $preg2 = "/document\.write|String\.fromCharCode/is";//去掉document.write和String.fromCharCode
        $preg3 = "/onload\s*=|onerror\s*=|onclick\s*=/is";//去掉onload=，onerror=,onclick=
        if (is_array($request)) {
            foreach ($request as $key => $val) {
                $request[$key] = self::pregParam($val);
            }
        } else {
            //xss过滤
            $request = preg_replace($preg, "", $request);
            $request = preg_replace($preg2, "", $request);
            $request = preg_replace($preg3, "", $request);
            //简单的sql过滤
            $request = addslashes($request);
        }
        Yii::$app->request->setBodyParams($request);//需要用setBodyParams方法把修改后的数据给回yii的输入参数，才能修改
        return $request;
    }

    /**
     * 正则匹配二级域名
     * @param $url
     * @return array|boolean
     */
    private static function getSLDMatches($url)
    {
        if (preg_match("/^(http:\/\/|https:\/\/)?([^\/:]+\.)?([^\/:]+\.[^\/:]+\..+)$/", $url, $matchs)) {
            return $matchs;
        } else {
            return false;
        }
    }

    /**
     * 获取对应域名的三级链接地址
     * @param  $host
     * @param  $wxid
     * @param  $url
     * @return boolean|string
     */
    public static function getTLD($host, $wxid, $url = "", $hostupdate = "")
    {
        $matchs = self::getSLDMatches($host);
        if (!empty($hostupdate)) {
            $matchs[3] = $hostupdate . "." . substr($matchs[3], (strpos($matchs[3], ".") + 1));
        }
        $redirct_url = $matchs[1] . $wxid . "." . $matchs[3];
        if ($host == $redirct_url) {
            return false;
        } else {
            return $redirct_url . $url;//需跳转地址
        }
    }

    /**
     * 获取导航栏数据
     * @param $wxid
     * @param $mall_url
     * @return array|string
     */
    public static function mainMenu($wxid, $mall_url)
    {
        $data_current = time();
        $source = self::is_weixin() ? 'wx' : 'other';//增加来源参数
        $sign = (new SecurityUtil(
            ['wxid' => $wxid, 'timestamp' => $data_current, 'source' => $source],
            Yii::$app->params['signKey']['voteSignKey']
        ))->generateSign();
        $url = $mall_url . '/api/get-navigation-bar?wxid=' . $wxid . '&sign=' . $sign . '&timestamp=' .
            $data_current . '&source=' . $source;
        $list_result = json_decode(self::get($url), true);
        if ($list_result['return_code'] == 'SUCCESS' && $list_result['return_msg']['return_code'] == 'SUCCESS') {
            $shoplist = [];
            foreach ($list_result['return_msg']['return_msg'] as $k => $v) {
                $shoplist[strtolower($k)] = $v;
            }
        } else {
            return '获取功能权限失败,请稍后再试!';
        }
        return $shoplist;
    }

    /**
     * 判断是否是微信
     */
    public static function is_weixin()
    {
        return strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ? true : false;
    }

}