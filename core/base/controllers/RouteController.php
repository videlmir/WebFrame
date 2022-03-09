<?php               //ОТВЕЧАЕТ ЗА РАЗБОР АДРЕСНОЙ СТРОКИ

namespace core\base\controllers;
use core\base\exceptions\RouteException;
use core\base\settings\Settings;


class RouteController extends BaseController
{
    use Singleton;

    protected $routes;

    private function __construct()
    {
        $address_str = $_SERVER['REQUEST_URI'];
        if (strrpos($address_str, '/') === strlen($address_str) - 1 && strrpos($address_str, '/') !== 0) {

            $this->redirect(rtrim($address_str, '/'), 301);

        }
        //в пхп селф с 0 берет колво элементов, равное позиции вхождения needle
        $path = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], 'index.php'));

        if ($path === PATH) {

            $this->routes = Settings::get('routes');

            if (!$this->routes) throw new RouteException('Отсутствуют маршруты в базовых настройках', 1);

            $url = explode('/', substr($address_str, strlen(PATH)));


            if ($url[0] && $url[0] === $this->routes['admin']['alias']) {
//админка//
                array_shift($url);

                if($url[0] && is_dir($_SERVER['DOCUMENT_ROOT'] . PATH . $this->routes['plugins']['path'] . $url[0])){

                    $plugin = array_shift($url);

                    $pluginSettings = $this->routes['settings']['path'] . ucfirst($plugin . 'Settings');

                    if(file_exists($_SERVER['DOCUMENT_ROOT']) . PATH . $pluginSettings . '.php'){

                        $pluginSettings = str_replace('/', '\\', $pluginSettings); //вдруг чего то переопределилось при подключении плагина

                        $this->routes = $pluginSettings::get('routes');
                    }

                    $dir = $this->routes['plugins']['dir'] ? '/' . $this->routes['plugins']['dir'] . '/' : '/';
                    $dir = str_replace('//', '/', $dir);

                    $this->controller = $this->routes['plugins']['routes'] . $plugin . $dir;

                    $hrUrl = $this->routes['plugins']['hrUrl'];

                    $route = 'plugins';

                }else{

                    $this->controller = $this->routes['admin']['path'];

                    $hrUrl = $this->routes['admin']['hrUrl'];

                    $route = 'admin';
                }




            } else {  //маршруты для пользовательской части//



                $hrUrl = $this->routes['user']['hrUrl'];

                $this->controller = $this->routes['user']['path'];

                $route = 'user';

            }
            $this->createRoute($route, $url);
            //ЧПУ или не ЧПУ. с какого элемента начать считать, что это ключ-значение. ЧПУ - 2.
            if($url[1]){
                $count = count($url);
                $key = '';

                if(!$hrUrl){
                    $i = 1;
                }else{
                    $this->parameters['alias'] = $url[1];
                    $i= 2;
                }
                for(; $i < $count; $i++){
                    if(!$key){
                        $key = $url[$i];
                        $this->parameters[$key] = '';

                    }else{
                        $this->parameters[$key] = $url[$i];
                        $key = '';
                    }
                }
            }

        } else {
                throw new RouteException('Некорректная директория сайта', 1);
        }
    }

    private function createRoute($var, $arr){
        $route = [];

        if(!empty($arr[0])){
            if($this->routes[$var]['routes'][$arr[0]]){
                $route = explode('/', $this->routes[$var]['routes'][$arr[0]]);
                $this->controller .= ucfirst($route[0] . 'Controller');
            }else{
                $this->controller .= ucfirst($arr[0] . 'Controller');
            }
        }else{
            $this->controller .= $this->routes['default']['controller'];
        }
        $this->inputMethod = $route[1] ? $route[1] : $this->routes['default']['inputMethod'];
        $this->outputMethod = $route[2] ? $route[2] : $this->routes['default']['outputMethod'];


    }

}