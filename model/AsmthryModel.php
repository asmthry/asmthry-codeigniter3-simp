<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once ASMTHRY_PATH . 'model/ModelResult.php';
require_once ASMTHRY_PATH . 'model/AsmthrySchema.php';

/**
 * select, get, first, find ,where, whereIn, limit, unique, drop
 * destroy, destroyAll, delete, count, create, notNull, createIfEmpty
 * bulkInsert, update, createOrUpdate, bulkCreateOrUpdate
 * whereNot, whereNotIn, orWhere, join, joinFn, selectFn
 * resetQuery, lastQuery, sub, with, hasMany, hasOne, latest
 */
class AsmthryModel
{
    public $data;
    private $with;

    public function __construct()
    {
        $CI = &get_instance();
        $this->database = $CI->load->database('', true);
    }

    private function _replaceTableName($string)
    {
        return str_replace('{table}', $this->table, $string);
    }

    private function _strToKey(string $string)
    {
        return preg_replace('/[^a-zA-Z0-9\']/', '_', $string);
    }

    public function select($select)
    {
        $this->database->select($this->_replaceTableName($select));
        return $this;
    }

    private function _replaceTablesName($fields, $value = null)
    {
        $result = [];
        if (is_array($fields)) {
            foreach ($fields as $key => $value) {
                $result[$this->_replaceTableName($key)] = $this->_replaceTableName($value);
            }
        } else {
            $value = $this->_replaceTableName($value);
            $result = $this->_replaceTableName($fields);
        }

        return [$result, $value];
    }

    public function from(string $table)
    {
        $this->database->from($table);
        return $this;
    }

    public function get()
    {
        $this->data = $this->database->get($this->table)->result_object();
        return $this->_getWithRelations();
    }

    public function first()
    {
        $this->data = $this->database->get($this->table)->row_object();
        return $this->_getWithRelations(true);
    }

    public function drop()
    {
        return $this->database->query("DROP TABLE IF EXISTS {$this->table}");
    }

    public function find($value, $column = 'id')
    {
        $this->database->where($this->_replaceTableName($column), $value);
        return $this;
    }

    public function latest()
    {
        $this->database->order_by('created_at', 'desc');
        return $this;
    }

    public function orderBy(string $column = 'id', string $direction = "asc")
    {
        $this->database->order_by($this->_replaceTableName($column), $direction);
        return $this;
    }

    public function where($key, $value = null)
    {
        [$key, $value] = $this->_replaceTablesName($key, $value);
        $this->database->where($key, $value);
        return $this;
    }

    public function whereIn($field, $array)
    {
        $this->database->where_in($this->_replaceTableName($field), $array);
        return $this;
    }

    public function limit($limit = 10, $start = 0)
    {
        $this->database->limit($limit, $start);
        return $this;
    }

    public function notNull(string $field)
    {
        $this->database->where("{$this->_replaceTableName($field)} is NOT NULL");
        return $this;
    }

    public function unique(string $field)
    {
        $this->database->distinct($this->_replaceTableName($field));
        return $this;
    }

    public function destroy($id)
    {
        return $this->where('id', $id)->delete();
    }

    public function groupBy(string $field)
    {
        $this->database->group_by($this->_replaceTableName($field));
        return $this;
    }

    private function _deleteAll()
    {
        if (isset($this->with) && !empty($this->with)) {
            $this->with->_deleteAll();
        }

        if (isset($this->localKey) && !empty($this->localKey)) {
            if (isset($this->hasOne) && $this->hasOne) {
                if ($this->data) {
                    $id = $this->data->{$this->localKey};
                    return $this->where($this->localKey, $id)->delete();
                } else {
                    return true;
                }
            } else {
                if ($this->data) {
                    $in = array_column($this->data, $this->localKey);
                    if ($in) {
                        foreach ($in as $value) {
                            $this->orWhere($this->localKey, $value);
                        }
                        return $this->delete();
                    } else {
                        return true;
                    }
                } else {
                    return true;
                }
            }
        }
    }

    public function destroyAll($id)
    {
        $this->find($id)->first();
        $this->_deleteAll();

        return $this->where('id', $id)->delete();
    }

    public function delete()
    {
        return $this->database->delete($this->table);
    }

    public function count()
    {
        $this->database->select('count(*) as count');
        if (isset($this->localKey) && !empty($this->localKey)) {
            $this->hasOne = true;
            $this->database
                ->group_by($this->foreignKey)
                ->select($this->foreignKey);
        }

        return $this;
    }

    public function create($data)
    {
        $this->database->insert($this->table, $data);
        return $this->database->insert_id();
    }

    public function bulkInsert($data)
    {
        return $this->database->insert_batch($this->table, $data);
    }

    public function update($data, $where = [])
    {
        if (!empty($where)) {
            $this->database->where($where);
        }

        return $this->database->update($this->table, $data);
    }

    public function createIfEmpty(array $data, string $key = null)
    {
        $this->resetQuery();
        if (is_null($key)) {
            $this->where($data);
        } else {
            $this->where($key, $data[$key]);
        }

        $getData = $this->first();

        if ($getData->isEmpty()) {
            return $this->create($data);
        }

        return true;
    }

    public function createOrUpdate(array $data, $keys = 'key')
    {
        $where = [];
        if (is_array($keys)) {
            foreach ($keys as $value) {
                $where[$value] = $data[$value];
            }
        } else {
            if (!isset($data[$keys])) {
                return $this->create($data);
            } else {
                $where[$keys] = $data[$keys];
            }
        }

        $getData = $this->where($where)->first();
        $this->resetQuery();

        if ($getData->isNotEmpty()) {
            $this->update($data, $where);
            return $getData->id;
        } else {
            return $this->create($data);
        }
    }

    public function bulkCreateOrUpdate(array $data, $keys = 'key')
    {
        foreach ($data as $value) {
            if (is_array($value)) {
                $this->createOrUpdate($value, $keys);
            }
        }

        return true;
    }

    public function whereNot($field, $value)
    {
        $this->database->where($field . "!=", $value);
        return $this;
    }

    public function whereNotIn($field, $values)
    {
        if ($values) {
            $this->database->where_not_in($field, $values);
        }
        return $this;
    }

    public function orWhere($field, $value)
    {
        $field = is_string($field) ? $this->_replaceTableName($field) : $field;
        $this->database->or_where($field, $value);
        return $this;
    }

    public function orWhereIn($field, array $value)
    {
        $field = is_string($field) ? $this->_replaceTableName($field) : $field;
        $this->database->or_where_in($field, $value);
        return $this;
    }

    public function groupStart()
    {
        $this->database->group_start();
        return $this;
    }

    public function orGroupStart()
    {
        $this->database->or_group_start();
        return $this;
    }

    public function groupEnd()
    {
        $this->database->group_end();
        return $this;
    }

    public function whereGroup(array $where)
    {
        return $this->groupStart()->where($where)->groupEnd();
    }

    public function orWhereGroup(array $where)
    {
        return $this->orGroupStart()->where($where)->groupEnd();
    }

    public function join($table, $condition, $type = '')
    {
        $this->database->join($table, $this->_replaceTableName($condition), $type);
        return $this;
    }

    private function callFunction(string $fn, string $prepend = '', $data = [])
    {
        $fnName = $prepend . ucfirst($fn);
        if (!method_exists($this, $fnName)) {
            throw new Exception("Invalid method {$fnName} in " . get_class($this));
        }

        return $this->$fnName($data);
    }

    public function joinFn($functions)
    {
        if (is_string($functions)) {
            return $this->callFunction($functions, 'join');
        }

        if (array($functions)) {
            $obj = $this;
            foreach ($functions as $key => $function) {
                $data = is_callable($function) ? call_user_func($function) : [];
                $function = is_callable($function) ? $key : $function;
                $obj = $this->callFunction($function, 'join', $data);
            }

            return $obj;
        }
    }

    public function selectFn($functions)
    {
        if (is_string($functions)) {
            return $this->callFunction($functions, 'select');
        }

        if (array($functions)) {
            $obj = $this;
            foreach ($functions as $function) {
                $obj = $this->callFunction($function, 'select');
            }

            return $obj;
        }
    }

    public function resetQuery()
    {
        $this->database->reset_query();
        return $this;
    }

    public function lastQuery()
    {
        $this->database->get($this->table);
        return $this->database->last_query();
    }

    public function sub()
    {
        return reset($this->with);
    }

    public function query(string $query, bool $single = false)
    {
        $result = $this->database->query($query)->{$single ? 'row' : 'result'}();

        return new ModelResult($result);
    }

    private function _getWithQuery($model, string $query, bool $single = false)
    {
        $result = $this->database->query($query)->{$single ? 'row' : 'result'}();
        $model->data = $result;

        if (property_exists($model, 'getOnRelation')) {
            return $model->_getWithRelations();
        }

        return new ModelResult($model);
    }

    private function _getResultWithLimit($model, array $in, bool $single = false)
    {
        $model->getOnRelation = true;
        $mainQuery = $model->whereIn($model->foreignKey, $in)->lastQuery();

        if (strpos($mainQuery, "LIMIT") === false) {
            return $this->_getWithQuery($model, $mainQuery, $single);
        }

        $replace = "(select *, row_number() over (partition by `{$model->foreignKey}`) as totalNumberOfRow 
        from {$model->table})";

        $whatIWant = explode(',', substr($mainQuery, strpos($mainQuery, "LIMIT") + 6));
        $query = substr($mainQuery, 0, strpos($mainQuery, "LIMIT"));
        if (!empty($whatIWant[1])) {
            $where = "totalNumberOfRow <= " . ((int)$whatIWant[1] + (int)$whatIWant[0]) . " AND ";
            $where .= "totalNumberOfRow >= {$whatIWant[0]} AND ";
        } else {
            $where = "totalNumberOfRow <= {$whatIWant[0]} AND ";
        }

        $query = substr_replace(
            $query,
            $where,
            strpos($query, "WHERE") + 6,
            0
        );
        $query = substr_replace(
            $query,
            $replace,
            strpos($query, " `{$model->table}"),
            0
        );

        return $this->_getWithQuery($model, $query, $single, true);
    }

    public function _getWithRelations($isFirst = false)
    {

        if (isset($this->with) && count($this->with)) {
            foreach ($this->with as $relationKey => $model) {
                if ($isFirst) {
                    $where = $this->data->{$model->localKey};
                    if ($where) {
                        if (isset($model->hasOne) && $model->hasOne) {
                            $result = $this->_getResultWithLimit($model, [$where], true);
                            $this->data->{$relationKey} = $result;
                        } else {
                            $result = $this->_getResultWithLimit($model, [$where]);
                            $this->data->{$relationKey} = $result;
                        }
                    }
                } else {
                    $in = array_unique(
                        array_column($this->data, $model->localKey)
                    );
                    if ($in) {
                        if (isset($model->hasOne) && $model->hasOne) {
                            $result = $this->_getResultWithLimit($model, $in);

                            foreach ($this->data as $key => $row) {
                                $this->data[$key]->{$relationKey} = $result->filter(
                                    $row->{$model->localKey},
                                    $model->foreignKey
                                )->first();
                            }
                        } else {
                            $result = $this->_getResultWithLimit($model, $in);

                            foreach ($this->data as $key => $row) {
                                $this->data[$key]->{$relationKey} = $result->filter(
                                    $row->{$model->localKey},
                                    $model->foreignKey
                                );
                            }
                        }
                    }
                }
            }
        }

        return new ModelResult($this);
    }

    private function _callWithFunction($fnName)
    {
        $relations = explode('.', $fnName);
        $model = $this;
        foreach ($relations as $fnName) {
            if (method_exists($model, $fnName)) {
                $model->with[$fnName] = $model->{$fnName}();
                $model->withFn = $fnName;
                $model = $model->with[$fnName];
            } else {
                throw new Exception("Invalid method {$fnName} in " . get_class($model));
            }
        }

        return reset($relations);
    }

    private function _callWithArrayFunction($methods)
    {
        foreach ($methods as $key => $value) {
            if (is_string($value)) {
                $this->_callWithFunction($value);
            }

            if (is_callable($value)) {
                $method = $this->_callWithFunction($key);
                call_user_func($value, $this->with[$method]);
            }
        }
    }

    public function with(...$argc)
    {
        $methods = func_get_args();

        foreach ($methods as $value) {
            if (is_string($value)) {
                $this->_callWithFunction($value);
            }

            if (is_array($value)) {
                $this->_callWithArrayFunction($value);
            }
        }
        return $this;
    }

    private function _makeRelationShip($model, $localKey, $foreignKey, $hasOne = false)
    {
        $CI = &get_instance();
        $modelName = $this->_strToKey($model);

        if (!class_exists($modelName)) {
            $CI->load->model($model, $modelName);
        }

        $model = new $CI->{$modelName};
        $model->localKey = $localKey;
        $model->foreignKey = $foreignKey;
        $model->hasOne = $hasOne;

        return $model;
    }

    private function _makeRelationShipUsingObject($model, $localKey, $foreignKey, $hasOne = false)
    {
        $model->localKey = $localKey;
        $model->foreignKey = $foreignKey;
        $model->hasOne = $hasOne;

        return $model;
    }

    public function hasMany($model, $localKey, $foreignKey)
    {
        if (is_object($model)) {
            return $this->_makeRelationShipUsingObject($model, $localKey, $foreignKey);
        } else {
            return $this->_makeRelationShip($model, $localKey, $foreignKey);
        }
    }

    public function hasOne($model, $localKey, $foreignKey)
    {
        if (is_object($model)) {
            return $this->_makeRelationShipUsingObject($model, $localKey, $foreignKey, true);
        } else {
            return $this->_makeRelationShip($model, $localKey, $foreignKey, true);
        }
    }

    public function migrate()
    {
        if (!method_exists($this, "schema")) {
            return false;
        }

        $schema = $this->schema(new AsmthrySchema);

        if (!($schema instanceof AsmthrySchema)) {
            throw new Exception("Invalid table schema");
        }

        $CI = &get_instance();
        $CI->load->dbforge();
        $CI->dbforge->add_field($schema->getFields());

        if ($schema->getKey()) {
            $CI->dbforge->add_key($schema->getKey(), true);
        }

        return $CI->dbforge->create_table($this->table, true);
    }
}
