<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once ASMTHRY_PATH . 'validations/Rules.php';

class AsmthryRequest
{
    use Rules;

    public $errors = [];
    public $data = [];
    public $method;
    public $type;
    public $rules = [];
    public $validated = [];
    public $headers = [];
    private $validator;
    private $input;

    public function __construct()
    {
        $this->validator = new MY_Form_validation();
        $this->input = new CI_Input();
        $this->method = $this->input->method(true);
        $this->headers = $this->input->request_headers();
        $this->type = $this->input->is_ajax_request() ? 'api' : 'cli';
        $this->handleInputValidation();
    }

    public function handleInputValidation()
    {
        if ($this->method == 'GET') {
            $this->data = $this->input->get();
        } elseif ($this->method == 'POST') {
            $this->data = $this->input->post();
        }

        if (empty($this->data)) {
            $data = $this->input->__get('raw_input_stream');
            $this->data = json_decode($data, true);
        }

        $this->data['_no_input_issue_fix'] = 'AsmthryRequest';

        $this->validator->set_data($this->data);
    }

    private function handleValidation($status = false)
    {
        if ($status) {
            foreach ($this->rules as $field => $rule) {
                $field = str_replace('[]', '', $field);
                $this->validated[$field] = $this->input($field);
            }
        } else {
            $this->errors = $this->validator->error_array();
        }

        if (!$this->wantJson()) {
            (get_instance())->form_validation = $this->validator;
        }

        return $status;
    }

    /**
     * This method will return value
     *
     * @param string $field The name of the field.
     */
    public function input($field, $default = null)
    {
        return (isset($this->data[$field]) &&
            !empty($this->data[$field])
        ) ? $this->data[$field] : $default;
    }

    /**
     * This method will return all values
     */
    public function all()
    {
        $data = $this->data;
        unset($data['_no_input_issue_fix']);
        return $data;
    }

    public function setRules(array $rules = [])
    {
        $this->rules = array_merge($this->rules, $rules);
        $this->validated = [];

        foreach ($this->rules as $field => $rule) {
            $this->validator->set_rules($field, strToTitle($field), $rule);
        }

        return $this;
    }

    public function validate(array $rules = [])
    {
        if (count($rules)) {
            $this->setRules($rules);
        }

        if (empty($this->rules)) {
            return true;
        }

        return $this->handleValidation($this->validator->run());
    }

    public function setData(array $array)
    {
        $this->validator->set_data($this->data = $array);
        return $this;
    }

    public function validated()
    {
        return $this->validated;
    }

    public function errors()
    {
        return $this->errors;
    }

    public function error(string $field)
    {
        return isset($this->errors[$field]) ? $this->errors[$field] : '';
    }

    public function setError(string $field, string $message, $index = null)
    {
        if ($index !== null) {
            $this->errors[$field][$index] = $message;
        } else {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function hasError()
    {
        return count($this->errors) > 0;
    }

    public function method(string $method)
    {
        return $this->method === strtoupper($method);
    }

    public function wantJson()
    {
        return array_key_exists('Accept', $this->headers) &&
            strtolower($this->headers['Accept']) === 'application/json';
    }

    public function setMessage(string $fn, string $message)
    {
        $this->validator->set_message($fn, $message);
        return $this;
    }

    public function setMessages(array $messages)
    {
        foreach ($messages as $key => $value) {
            $this->setMessage($key, $value);
        }
        return $this;
    }

    public function hasFile(string $field)
    {
        return isset($_FILES[$field]) && !empty($_FILES[$field]['name']);
    }

    public function headers()
    {
        return $this->headers;
    }

    public function header(string $name)
    {
        return key_exists($name, $this->headers) ? $this->headers[$name] : false;
    }

    public function flashError($key, $message)
    {
        (get_instance())->session->set_flashdata($key, $message);
        return $this;
    }

    public function doUpload(
        string $field,
        string $path = 'uploads/',
        $type = 'any',
        $index = null,
        string $customName = null
    ) {
        $allowedType = array(
            "any" => array(
                "jpg", "jpeg", "png", "gif", "wmv", "zip", "rar", "doc", "docx",
                "mp4", "avi", "mov", "pdf", "mp3", "wav", "opus"
            ),
            "image" => array("jpg", "jpeg", "png", "gif"),
            "video" => array("wmv", "mp4", "avi", "mov"),
            "file" => array("pdf"),
            "document" => array("doc", "docx"),
            "audio" => array("mp3", "wav", "opus"),
            'compressed' => array("zip", "rar")
        );

        if (!isset($_FILES[$field]) || ($index !== null && !isset($_FILES[$field]['name'][$index]))) {
            return;
        }

        $fileName = ($index !== null ? $_FILES[$field]['name'][$index] : $_FILES[$field]['name']);
        $tempName = ($index !== null ? $_FILES[$field]['tmp_name'][$index] : $_FILES[$field]['tmp_name']);
        $fileSize = ($index !== null ? $_FILES[$field]['size'][$index] : $_FILES[$field]['size']);

        if ($fileSize > 10485760) {
            $message = "File size too large,File size must be less than 10MB";
            if ($index !== null) {
                $this->errors[$field][$index] = $message;
            } else {
                $this->errors[$field] = $message;
            }

            $this->flashError("error_{$field}", $message);
            return false;
        }

        $fileInfo = pathinfo($fileName);
        $extension = strtolower($fileInfo['extension']);

        if (!in_array($extension, $allowedType[$type])) {
            $message = "Invalid file format";
            if ($index !== null) {
                $this->errors[$field][$index] = $message;
            } else {
                $this->errors[$field] = $message;
            }
            $this->flashError("error_{$field}", $message);
            return false;
        }

        $path .= (is_null($customName) ?
            $fileInfo['filename'] . "_" . time() :
            $customName
        ) . "." . $extension;

        if (move_uploaded_file($tempName, $path)) {
            return $path;
        } else {
            $message = "Something went to wrong/Internal server Error.";
            $this->errors[$field] = $message;
            $this->flashError("error_{$field}", $message);
            return false;
        }
    }
}
