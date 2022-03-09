<?php

namespace core\base\settings;

use core\base\controllers\Singleton;
use core\base\settings\Settings;

class ShopSettings
{
    use Singleton;

    private $baseSettings;

    private $routes = [
        'plugins' => [
            'dir' => false,
            'routes' => [

                ]
            ]

    ];

    private $templateArr = [
        'text' => ['price', 'short'],
        'textarea' => ['goods_content']
    ];


    static public function get($property){
        return self::getInstance()->$property;
    }

    static private function getInstance(){
        if(self::$_instance instanceof self){
            return self::$_instance;
        }
        //склейка настроек(если подключается плагин и изменяет что-то)

        self::instance()->baseSettings = Settings::instance();
        $baseProperties = self::instance()->baseSettings->glueProperties(get_class());
        self::$_instance->setProperty($baseProperties);

        return self::$_instance;

    }
    protected function setProperty($properties){
        if($properties){
            foreach ($properties as $name => $property){
                $this->$name =  $property;
            }
        }
    }



}