<?php
defined('BASEPATH') or exit('No direct script access allowed');

trait AsmthryControllerTrait
{
    private $modelInstance;

    private function _strToKey(string $string)
    {
        return preg_replace('/[^a-zA-Z0-9\']/', '_', $string);
    }

    private function _initModel()
    {
        if (!property_exists($this, 'model')) {
            return false;
        }

        $modelName = (property_exists($this, 'modelAs'))
            ? $this->_strToKey($this->modelAs)
            : $this->_strToKey($this->model);

        $this->load->model($this->model, $modelName);
        $this->modelInstance = $this->{$modelName};
    }

    public function request()
    {
        $request = new AsmthryRequest();

        if (method_exists($this, 'rules')) {
            $request->setRules($this->rules($request));
        } elseif (property_exists($this, 'rules')) {
            $request->setRules($this->rules);
        } else {
            $request->setRules();
        }

        return $request;
    }

    public function view(string $view, array $data = [], bool $load = false)
    {
        return $this->load->view($view, $data, $load);
    }

    public function newModel(string $model = null, string $modelName = null)
    {
        $modelName = $modelName == null ? $model : $modelName;
        $modelName = $this->_strToKey($modelName);
        $this->load->model($model, $modelName);
        return $this->{$modelName};
    }

    public function theModel()
    {
        return $this->modelInstance;
    }

    public function redirect(string $url, int $statusCode = null, string $method = 'auto')
    {
        return redirect($url, $method, $statusCode);
    }

    public function back()
    {
        $uri = $this->input->server('HTTP_REFERER');
        if (!$uri) {
            return $this->redirect('404');
        }
        return $this->redirect($uri);
    }

    public function setSession(string $key, $values)
    {
        $this->session->set_userdata($key, $values);
        return $this;
    }

    public function flash($key, $value)
    {
        $this->session->set_flashdata($key, $value);
        return $this;
    }
}
