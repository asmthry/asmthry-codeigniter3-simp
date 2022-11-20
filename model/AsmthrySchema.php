<?php

defined('BASEPATH') or exit('No direct script access allowed');

class AsmthrySchema
{
    private $schema = [];
    private $currentField = null;
    private $primaryKey = null;

    private function validateCurrentField()
    {
        if (empty($this->currentField)) {
            throw new Exception("Table field not found");
        }

        return true;
    }

    public function getFields()
    {
        return $this->schema;
    }

    public function getKey()
    {
        return $this->primaryKey;
    }

    public function primary()
    {
        $this->primaryKey = $this->currentField;
        return $this;
    }

    public function unsigned()
    {
        $this->validateCurrentField();
        $this->schema[$this->currentField]['unsigned'] = true;
        return $this;
    }

    public function auto()
    {
        $this->validateCurrentField();
        $this->schema[$this->currentField]['auto_increment'] = true;
        return $this;
    }

    public function default(string $value)
    {
        $this->validateCurrentField();
        $this->schema[$this->currentField]['default'] = $value;
        return $this;
    }

    public function unique()
    {
        $this->validateCurrentField();
        $this->schema[$this->currentField]['unique'] = true;
        return $this;
    }

    public function null()
    {
        $this->validateCurrentField();
        $this->schema[$this->currentField]['null'] = true;
        return $this;
    }

    public function notNull()
    {
        $this->validateCurrentField();
        $this->schema[$this->currentField]['null'] = false;
        return $this;
    }

    public function string(string $name, int $length = 255)
    {
        $this->schema[$name] = array(
            'type' => 'VARCHAR',
            'constraint' => $length
        );
        $this->currentField = $name;

        return $this;
    }

    public function integer(string $name, int $length = 11)
    {
        $this->schema[$name] = array(
            'type' => 'INT',
            'constraint' => $length
        );
        $this->currentField = $name;

        return $this;
    }
}
