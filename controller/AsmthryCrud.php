<?php
defined('BASEPATH') or exit('No direct script access allowed');

trait AsmthryCrud
{
    abstract protected function indexView();

    public function filterData($model)
    {
        return $model;
    }

    private function checkPermission(string $action)
    {
        if (!property_exists($this, "permission")) {
            return true;
        }

        return $this->can($this->permission, $action);
    }

    public function index()
    {
        // if (!$this->checkPermission("can_view")) {
        //     return $this->unauthorizedView();
        // }

        $model = $this->filterData($this->theModel());
        $this->setData("results", $model->get());

        return $this->indexView();
    }

    public function create()
    {
        // if (!$this->checkPermission("can_add")) {
        //     return $this->unauthorizedView();
        // }

        // $this->checkPermission("create");

        if (property_exists($this, "loadAll") && $this->loadAll) {
            $model = $this->filterData($this->theModel());
            $this->setData("results", $model->get());
        }

        if (method_exists($this, "createView")) {
            return $this->createView();
        }

        return $this->indexView();
    }

    public function store()
    {
        // if (!$this->checkPermission("can_add")) {
        //     return $this->unauthorizedView();
        // }

        $request = $this->request();

        if (!$request->validate()) {
            return $this->create();
        }

        $data = $request->validated();

        $this->theModel()->create($data);
        $url = str_replace("store", "index", current_url());

        if (method_exists($this, "filterRedirect")) {
            $url = $this->filterRedirect($url, "store");
        }

        return $this->flash("status", "success")->redirect($url);
    }

    public function edit(int $id)
    {
        // if (!$this->checkPermission("can_edit")) {
        //     return $this->unauthorizedView();
        // }

        $model = $this->filterData($this->theModel()->find($id));
        $this->setData("result", $model->first());

        if (property_exists($this, "loadAll") && $this->loadAll) {
            $model = $this->filterData($this->theModel());
            $this->setData("results", $model->get());
        }

        if (method_exists($this, "editView")) {
            return $this->editView();
        }

        return $this->indexView();
    }

    public function update(int $id)
    {
        // if (!$this->checkPermission("can_edit")) {
        //     return $this->unauthorizedView();
        // }

        $request = $this->request();

        if (!$request->validate()) {
            return $this->edit($id);
        }

        $data = $request->validated();

        $this->theModel()->find($id)->update($data);
        $url = str_replace("update/{$id}", "index", current_url());

        if (method_exists($this, "filterRedirect")) {
            $url = $this->filterRedirect($url, "update");
        }

        return $this->flash("status", "success")->redirect($url);
    }

    public function delete(int $id)
    {
        // if (!$this->checkPermission("can_delete")) {
        //     return $this->unauthorizedView();
        // }

        $url = str_replace("delete/{$id}", "index", current_url());

        $this->theModel()->where("id", $id)->delete();

        if (method_exists($this, "filterRedirect")) {
            $url = $this->filterRedirect($url, "delete");
        }

        return $this->flash("status", "success")
            ->flash("message", "Record deleted")->redirect($url);
    }
}
