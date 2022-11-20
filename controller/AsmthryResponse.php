<?php
defined('BASEPATH') or exit('No direct script access allowed');

trait AsmthryResponse
{
    protected function sendResponse($data, $status_code = 200)
    {
        $response = $this->output
            ->set_content_type('application/json')
            ->set_status_header($status_code);

        if (!is_null($data)) {
            $response->set_output(json_encode($data));
        }

        return $response;
    }

    protected function sendErrors(array $errors = [], $message = 'Request contains invalid data')
    {
        return $this->sendResponse(array(
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ), 422);
    }

    protected function sendAccessDenied()
    {
        return $this->sendResponse(array(
            'status' => 'error',
            'message' => 'Access Forbidden',
            'errors' => []
        ), 403);
    }

    protected function sendBadMethod()
    {
        return $this->sendResponse(array(
            'status' => 'error',
            'message' => 'Bad request',
            'errors' => []
        ), 400);
    }

    public function sendSuccess($data = [])
    {
        return $this->sendResponse([
            'status' => 'success',
            'message' => 'Success',
            'data' => $data
        ]);
    }

    public function sendCreated($data)
    {
        return $this->sendResponse([
            'status' => 'success',
            'message' => 'created',
            'data' => $data
        ], 201);
    }

    public function sendNoData()
    {
        return $this->sendResponse([
            'status' => 'success'
        ], 204);
    }

    public function sendNotFound()
    {
        return $this->sendResponse([
            'status' => 'error',
            'message' => "The requested resource was not found."
        ], 404);
    }
}
