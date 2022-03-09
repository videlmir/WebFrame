<?php

namespace core\base\settings;

use core\base\controllers\Singleton;

class Settings
{
    use Singleton;

    private $routes = [
      'admin' => [
          'alias' => 'admin',
          'path' => 'core/admin/controllers/',
          'hrUrl' => false
      ],
      'settings' => [
          'path' => 'core/base/settings/'
      ],
      'plugins' => [
          'path' => 'core/plugins/',
          'hrUrl' => false
      ],
      'user' => [
          'path' => 'core/user/controllers/',
          'hrUrl' => true,
          'routes' => [

          ]
      ],
      'default' => [
          'controller' => 'IndexController',
          'inputMethod' => 'inputData',
          'outputMethod' => 'outputData'
      ]
    ];
    private $la = 'la';

    private $templateArr = [
        'text' => ['name', 'phone', 'address'],
        'textarea' => ['content', 'keyword']
    ];


    static public function get($property){
        return self::instance()->$property;
    }


    public function glueProperties($class){  //склейка свойств дефолтных и плагина
        $baseProperties = [];

        foreach ($this as $name => $item){
            $property = $class::get($name);

            if(is_array($property) && is_array($item)){

                $baseProperties[$name] = $this->arrayMergeRecursive($this->$name, $property);
                continue;

            }

            if (!$property) $baseProperties[$name] = $item; // or this->name

        }
        return $baseProperties;
    }
    public function arrayMergeRecursive(){
        $arrays = func_get_args();
        $base = array_shift($arrays);

        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                if (is_array($base[$key]) && is_array($value)) {
                    $base[$key] = $this->arrayMergeRecursive($base[$key], $value);
                } else {
                    if (is_int($key)) {
                        if (!in_array($value, $base)) array_push($base, $value);
                        continue;
                    }
                    $base[$key] = $value;

                }
            }

        }
        return $base;
    }
}