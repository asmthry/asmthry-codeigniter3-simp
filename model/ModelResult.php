<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ModelResult
{
    public function __construct($param)
    {
        $this->_setData($param);
    }

    private function _setData($param)
    {
        if (is_object($param) && $param instanceof AsmthryModel) {

            if (empty($param->data)) {
                return;
            }

            foreach ($param->data as $key => $value) {
                if (method_exists($param, 'afterGet')) {
                    $value = $param->afterGet($value);
                }
                $this->{$this->_strToKey($key)} = $value;
            }
        } else {
            foreach ($param as $key => $value) {
                $this->{$this->_strToKey($key)} = $value;
            }
        }
    }

    private function _strToKey(string $string)
    {
        return preg_replace('/[^a-zA-Z0-9\']/', '_', $string);
    }

    private function _getData()
    {
        $data =  get_object_vars($this);
        return $data ? $data : [];
    }

    public function items()
    {
        return $this->_getData();
    }

    public function count()
    {
        $data = $this->_getData();
        return $data ? count($data) : 0;
    }

    public function toJson()
    {
        return json_encode($this->_getData());
    }

    public function toArray()
    {
        return json_decode($this->toJson(), true);
    }

    public function first()
    {
        $data = $this->_getData();
        $firstData = reset($data);
        return $firstData ? new ModelResult($firstData) : $this;
    }

    public function last()
    {
        $data = $this->_getData();
        $endData = end($data);
        return $endData ? new ModelResult($endData) : $this;
    }

    public function isEmpty()
    {
        return $this->count() === 0;
    }

    public function isNotEmpty()
    {
        return $this->count() !== 0;
    }

    public function filter($value, $field = 'name')
    {
        $filtered = array_filter(
            $this->_getData(),
            function ($data) use ($field, $value) {
                $explode = explode('->', $field);
                $temp = $data;
                foreach ($explode as $key) {
                    $temp = isset($temp->{$key}) ? $temp->{$key} : '';
                }
                return $temp == $value;
            }
        );

        return new ModelResult(array_values($filtered));
    }

    public function find(int $id)
    {
        $data = $this->filter($id, 'id')->first();
        return new ModelResult($data);
    }

    public function map(callable $callable)
    {
        return array_map($callable, $this->_getData());
    }

    public function keyBy($key = 'id')
    {
        $data = [];
        foreach ($this->_getData() as $value) {
            $data[$value->{$key}][] = $value;
        }

        return new ModelResult($data);
    }
}
