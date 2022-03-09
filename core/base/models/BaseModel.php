<?php

namespace core\base\models;

use core\base\controllers\Singleton;
use core\base\exceptions\DbException;
use function Sodium\add;

class BaseModel extends BaseModelMethods
{

    use Singleton;

    protected $db;

    private function __construct()
    {
        $this->db = @new \mysqli(HOST, USER, PASS, DB_NAME);

        if ($this->db->connect_error) {
            throw new DbException('Ошибка подключения к базе данных: '
                . $this->db->connect_errno . ' ' . $this->db->connect_error);

        }

        $this->db->query('SET NAMES UTF8');
    }

    /**
     * @param $query
     * @param $crud = r - SELECT / c - INSERT / u - UPDATE / d - DELETE
     * @param $return_id
     * @return array|bool
     * @throws DbException
     */

    final public function query($query, $crud = 'r', $return_id = false)
    {

        $result = $this->db->query($query);

        if ($this->db->affected_rows === -1) {
            throw new DbException('Ошибка  в SQL запросе: '
                . $query . ' - ' . $this->db->errno . ' ' . $this->db->error);
        }

        switch ($crud) {
            case 'r':
                if ($result->num_rows) {

                    $res = [];

                    for ($i = 0; $i < $result->num_rows; $i++) {

                        $res[] = $result->fetch_assoc();
                    }
                    return $res;

                }
                return false;

                break;

            case 'c':
                if ($return_id) return $this->db->insert_id;

                return true;

                break;

            default: //во всех отсальных случаях

                return true;

                break;


        }
    }

    /**
     * @param $table
     * @param $set
     * 'fields' => ['id', 'name'],
     * 'where' => ['id' => 1, 'name' => 'Masha'],
     * 'operand' => ['=', '<>'],
     * 'condition' => ['AND'],
     * 'order' => ['fio', 'name'],
     * 'order_direction' => ['ASC', 'DESC'],
     * 'link' => '1'
     */
    final public function get($table, $set = [])
    {

        $fields = $this->createFields($set, $table);

        $order = $this->createOrder($set, $table);

        $where = $this->createWhere($set, $table);

        if (!$where) $new_where = true;
        else $new_where = false;

        $join_arr = $this->createJoin($set, $table, $new_where);

        $fields .= $join_arr['fields'];
        $join = $join_arr['join'];
        $where .= $join_arr['where'];

        $fields = rtrim($fields, ',');


        $limit = $set['limit'] ? 'LIMIT ' . $set['limit'] : '';

        $query = "SELECT $fields FROM $table $join $where $order $limit";


        return $this->query($query);
    }


    /**
     * @param $table - таблица для вставки данных
     * @param array $set - массив параметров:
     * fields => [поле => значение]; - если не указан, то обрабатывается $_POST[поле => значение]
     * разрешена передача например NOW() в качестве MySql функции обычной строкой
     * files => [поле => значение]; - можно подать массив вида [поле => [массив значений]]
     * except => ['исключение 1', 'исключение 2'] - исключает данные элементы массива из       добавленных в запрос
     * return_id => true | false - возвращать или нет идентификатор вставленной записи
     *@return mixed
     */

    final public function add($table, $set = []){

        $set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : $_POST;
        $set['files'] = (is_array($set['files']) && !empty($set['files'])) ? $set['files'] : false;

        if(!$set['files'] && !$set['fields']) return false;

        $set['return_id'] = $set['return_id'] ? true : false;
        $set['except'] = (is_array($set['except']) && !empty($set['except'])) ? $set['except'] : false;

        $insert_arr = $this->createInsert($set['fields'], $set['files'], $set['except']);

        if($insert_arr){

            $query = "INSERT INTO $table ({$insert_arr['fields']}) VALUE ({$insert_arr['values']})";

            return $this->query($query, 'c', $set['return_id']);


        }

        return false;
    }

    final public function edit($table, $query, $set = []){

        $set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : $_POST;
        $set['files'] = (is_array($set['files']) && !empty($set['files'])) ? $set['files'] : false;

        if(!$set['files'] && !$set['fields']) return false;

        $set['except'] = (is_array($set['except']) && !empty($set['except'])) ? $set['except'] : false;

        if(!$set['all_rows']){

            if($set['where']){

                $where = $this->createWhere($set);

            }else{

                $columns = $this->showColumns($table);

                if(!$columns) return false;

                if($columns['id_row'] && $set['fields'][$columns['id_row']]){
                    $where = 'WHERE ' . $columns['id_row'] . '=' . $set['fields'][$columns['id_row']];
                    unset($set['fields'][$columns['id_row']]);

                }

            }
        }

        $update = $this->createUpdate($set['fields'], $set['files'], $set['except']);

        $update = "UPDATE $table SET $update $where";

        return $this->query($query, 'u');

    }

    final public function showColumns($table)
    {

        $query = "SHOW COLUMNS FROM $table";

        $res = $this->query($query);

        $columns = [];

        if($res){

            foreach($res as $row){

                $columns[$row['Field']] = $row;

                if($row['Key'] === 'PRI') $columns['id_row'] = $row['Field'];
            }

            return $columns;
        }



    }

}