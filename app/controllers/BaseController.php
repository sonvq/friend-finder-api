<?php

class BaseController extends Controller {

    /**
     * Setup the layout used by the controller.
     *
     * @return void
     */
    protected function setupLayout() {
        if (!is_null($this->layout)) {
            $this->layout = View::make($this->layout);
        }
    }

    protected function processInput() {
        $input = Input::all();

        $result = array();

        $fields = array();
        if (array_key_exists('fields', $input)) {
            $fields = explode(',', $input['fields']);
            unset($input['fields']);
        }

        $sort = array();
        if (array_key_exists('sort', $input)) {
            foreach (explode(',', $input['sort']) as $sortValue) {
                if (substr($sortValue, 0, 1) == '-') {
                    $sort[substr($sortValue, 1)] = 'Desc';
                } else {
                    $sort[$sortValue] = 'Asc';
                }
            }

            unset($input['sort']);
        }

        $limit = 10;
        if (array_key_exists('limit', $input)) {
            $limit = $input['limit'];
            unset($input['limit']);
        }

        $offset = 0;
        if (array_key_exists('offset', $input)) {
            $offset = $input['offset'];
            unset($input['offset']);
        }

        $where = $input;

        $result['fields'] = $fields;
        $result['sort'] = $sort;
        $result['limit'] = $limit;
        $result['offset'] = $offset;
        $result['where'] = $where;

        return $result;
    }

}
