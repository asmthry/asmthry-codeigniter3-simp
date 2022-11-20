<?php
defined('BASEPATH') or exit('No direct script access allowed');

trait Rules
{
    public function required()
    {
        return array(
            'required',
            function ($value, $field) {
                $value = empty($value) ? $this->input($field['field']) : $value;
                $this->setMessage(
                    'required',
                    "The {field} field is required."
                );

                return (is_array($value)
                    ? !empty($value)
                    : (trim($value) !== ''));
            }
        );
    }

    public function requiredIf($field)
    {
        return array(
            'requiredIf',
            function ($value) use ($field) {
                $data = $this->input($field);
                $this->setMessage(
                    'requiredIf',
                    "The {field} field is required if " . strToTitle($field) . " field is present"
                );

                if ((is_array($data)
                    ? empty($data) === false
                    : (trim($data) === ''))) {
                    return true;
                }

                return (is_array($value)
                    ? empty($value) !== false
                    : (trim($value) !== ''));
            }
        );
    }

    public function requiredNotIf($field)
    {
        return array(
            'requiredIf',
            function ($value) use ($field) {
                $data = $this->input($field);
                $this->setMessage(
                    'requiredIf',
                    "The {field} field is required if " . strToTitle($field) . " field is empty"
                );

                if ((is_array($data)
                    ? empty($data) !== false
                    : (trim($data) !== ''))) {
                    return true;
                }

                return (is_array($value)
                    ? empty($value) !== false
                    : (trim($value) !== ''));
            }
        );
    }

    /**
     * Validate a field based on the condition
     * 
     * @param bool $boolean If true this field will be required.
     * @param string $fileField Required if you want to validate a field
     */
    public function requiredWhere(bool $boolean, string $fileField = 'file')
    {
        return array(
            'requiredWhere',
            function ($value) use ($boolean, $fileField) {
                $this->setMessage(
                    'requiredWhere',
                    "The {field} field is required."
                );

                if (!$boolean) {
                    return true;
                }

                $status = is_array($value)
                    ? empty($value) !== false
                    : (trim($value) !== '');

                return $status || $this->hasFile($fileField);
            }
        );
    }

    /**
     * @param string $field Field name of the parent input field.
     * @param string $table Table name that the parent table field exist.
     * @param string $parentTableField - Name of the parent table field in current table.
     *               $field name will take as default.
     * @param string $tableField  Current input value in which field.
     */
    public function hasRelation(
        string $field,
        string $table,
        string $parentTableField = null,
        string $tableField = 'id'
    ) {
        return array(
            'hasRelation',
            function ($value) use ($field, $table, $parentTableField, $tableField) {
                $this->setMessage(
                    'hasRelation',
                    "Invalid {field}. Please enter valid {field} that match with " . strTotitle($field)
                );

                $CI = get_instance();
                if (is_array($value)
                    ? empty($value) === false
                    : (trim($value) === '')
                ) {
                    return true;
                };

                return $CI->db
                    ->where(
                        $parentTableField !== null ? $parentTableField : $field,
                        $this->input($field)
                    )
                    ->where($tableField, $value)
                    ->get($table)
                    ->num_rows() > 0;
            }
        );
    }

    /**
     * @param string $table Table name.
     * @param string $column Table column name.
     * @param string $multiFields multiple field check key as column name and value as field name.
     */
    public function unique(string $table, string $column, array $multiFields = [], array $not = [])
    {
        return array(
            'unique',
            function ($value) use ($table, $column, $multiFields, $not) {
                $CI = get_instance();

                if (is_array($value)
                    ? empty($value) === false
                    : (trim($value) === '')
                ) {
                    $this->setMessage(
                        'unique',
                        "The {field} is required."
                    );
                    return true;
                };

                $this->setMessage(
                    'unique',
                    "The details already exist."
                );

                $query = $CI->db;


                if (!empty($multiFields)) {
                    foreach ($multiFields as $mColumn => $mValue) {
                        $query->where($mColumn, $this->input($mValue));
                    }
                }

                $explode = explode("/", current_url());
                $id = end($explode);
                if (is_numeric($id)) {
                    $query->where("id!=", $id);
                }

                return $query->where($column, $value)
                    ->get($table)
                    ->num_rows() <= 0;
            }
        );
    }

    /**
     * @param string $table Table name.
     * @param array $fields Table column name and field name [table_column=>field_name].
     */
    public function mathTableRow(string $table, array $fields)
    {
        # code...
    }
}
