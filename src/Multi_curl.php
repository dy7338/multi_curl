<?php
namespace Multi_Curl;


/**
 * User: xiaole
 * Date: 17/3/1
 * Time: 下午3:20
 * Blog: http://www.xiaole88.com
 */
class Multi_curl {


    public $timeout = 5;

    private $result;
    private $requests = array ();


    function __construct () {
        $this->result = curl_multi_init ();
    }

    public function add_post_url ($key, $url, array $post = array (), array $options = array ()) {

        $defaults = array (
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST           => 1,
            CURLOPT_HEADER         => 0,
            CURLOPT_URL            => $url,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_POSTFIELDS     => http_build_query ($post, '', '&'),
        );

        $ch = curl_init ();
        curl_setopt_array ($ch, ($options + $defaults));

        return $this->addCurl ($key, $ch);
    }

    public function add_get_url ($key, $url, array $get = array (), array $options = array ()) {

        $defaults = array (
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST           => 1,
            CURLOPT_HEADER         => 0,
            CURLOPT_URL            => $url . (strpos ($url, '?') === false ? '?' : '') . http_build_query ($get, '', '&'),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 3,
        );

        $ch = curl_init ();
        curl_setopt_array ($ch, ($options + $defaults));

        return $this->addCurl ($key, $ch);
    }

    private function addCurl ($key, $ch) {
        $this->requests[(string)$key] = $ch;

        $code = curl_multi_add_handle ($this->result, $ch);

        if ($code !== CURLM_OK) {
            return false;
        }
    }

    public function multi_exec () {

        $data = array ();

        //无批量执行句柄，返回空数组
        if (is_null ($this->result)) {
            return $data;
        }

        //执行
//        do {
//            $mrc = curl_multi_exec ($this->result, $active);
//        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
//        while ($active && $mrc == CURLM_OK) {
//            if (curl_multi_select ($this->result) != -1) {
//                do {
//                    $mrc = curl_multi_exec ($this->result, $active);
//                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
//            }
//        }

        $active = false;
        do {
            while (($mrc = curl_multi_exec ($this->result, $active)) == CURLM_CALL_MULTI_PERFORM) {
                ;
            }
            if ($active && curl_multi_select ($this->result) === -1) {
                // Perform a usleep if a select returns -1: https://bugs.php.net/bug.php?id=61141
                usleep (150);
            }
        } while ($active);


        //获取返回结果
        foreach ($this->requests as $k => $ch) {
            $data[$k] = curl_multi_getcontent ($ch);

//            $errno = curl_errno ($ch);
//            $error = curl_error ($ch);

            curl_multi_remove_handle ($this->result, $ch);//移除句柄
            curl_close ($ch);//关闭会话
        }


        return $data;
    }

    public function __destruct () {
        curl_multi_close ($this->result);
    }


}