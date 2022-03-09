<?php

namespace core\admin\controllers;

use core\base\controllers\BaseController;
use core\admin\models\Model;

class IndexController extends BaseController
{
    protected function inputData(){

        $db = Model::instance();

        $table = 'teachers';

        $files['gallery_img'] = ["red.jpg", 'blue.jpg', 'green.jpg'] ;
        $files['img'] = 'main_img.jpg';


        $res = $db->showColumns($table);




        exit('id =' . $res['id'] . ' Name = ' . $res['name']);
    }
}