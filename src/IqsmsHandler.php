<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\sms\iqsms;

use skeeks\cms\sms\SmsHandler;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\NumberField;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class IqsmsHandler extends SmsHandler
{
    public $host = "gate.iqsms.ru";
    public $port = "80";
    public $login = "";
    public $password = "";
    public $wapurl = false;
    public $sender = "";

    /**
     * @return array
     */
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => \Yii::t('skeeks/shop/app', 'Iqsms'),
        ]);
    }


    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['host'], 'required'],
            [['port'], 'required'],
            [['login'], 'required'],
            [['password'], 'required'],

            [['host'], 'string'],
            [['port'], 'integer'],
            [['login'], 'string'],
            [['password'], 'string'],

            [['sender'], 'string'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'host' => "Сервер отправки",
            'port'   => "Порт",

            'login'    => "Логин",
            'password' => "Пароль",

            'sender' => "Отправитель по умолчанию",

        ]);
    }

    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [

        ]);
    }

    protected function _send($to, $text, $sender = null) {
        $fp = fsockopen($this->host, $this->port, $errno, $errstr);
        if (!$fp) {
            return "errno: $errno \nerrstr: $errstr\n";
        }

        fwrite($fp, "GET /send/".
            "?phone=".rawurlencode($to).
            "&text=".rawurlencode($text).
            ($sender ? "&sender=".rawurlencode($sender) : "").
            ($this->wapurl ? "&wapurl=".rawurlencode($this->wapurl) : "").
            " HTTP/1.0\n");
        fwrite($fp, "Host: ".$this->host."\r\n");
        if ($this->login != "") {
            fwrite($fp, "Authorization: Basic ".
                base64_encode($this->login.":".$this->password)."\n");
        }
        fwrite($fp, "\n");
        $response = "";
        while (!feof($fp)) {
            $response .= fread($fp, 1);
        }
        fclose($fp);
        list($other, $responseBody) = explode("\r\n\r\n", $response, 2);

        $data = explode("=", $responseBody);
        if (count($data) == 2) {
            return $data[0];
        }

        throw new Exception($responseBody);
        //return $responseBody;
    }

    /*
    * функция проверки состояния отправленного сообщения
    */
    public function status($sms_id)
    {
        $fp = fsockopen($this->host, $this->port, $errno, $errstr);
        if (!$fp) {
            return "errno: $errno \nerrstr: $errstr\n";
        }
        fwrite($fp, "GET /status/" .
            "?id=" . $sms_id .
            " HTTP/1.0\n");
        fwrite($fp, "Host: " . $this->host . "\r\n");
        if ($this->login != "") {
            fwrite($fp, "Authorization: Basic " .
                base64_encode($this->login. ":" . $this->password) . "\n");
        }
        fwrite($fp, "\n");
        $response = "";
        while(!feof($fp)) {
            $response .= fread($fp, 1);
        }
        fclose($fp);
        list($other, $responseBody) = explode("\r\n\r\n", $response, 2);
        return $responseBody;
    }



    /**
     * @return array
     */
    public function getConfigFormFields()
    {
        return [
            'main'    => [
                'class'  => FieldSet::class,
                'name'   => 'Основные',
                'fields' => [
                    'login',
                    'password',
                    'sender',
                ],
            ],
            'default' => [
                'class'  => FieldSet::class,
                'name'   => 'Прочее',
                'fields' => [
                    'host',

                    'port' => [
                        'class' => NumberField::class,
                    ],
                ],
            ],
        ];
    }
}