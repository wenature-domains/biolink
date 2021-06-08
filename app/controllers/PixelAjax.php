<?php

namespace Altum\Controllers;

use Altum\Database\Database;
use Altum\Date;
use Altum\Middlewares\Authentication;
use Altum\Middlewares\Csrf;
use Altum\Response;

class PixelAjax extends Controller {

    public function index() {

        Authentication::guard();

        if(!empty($_POST) && (Csrf::check('token') || Csrf::check('global_token')) && isset($_POST['request_type'])) {

            switch($_POST['request_type']) {

                /* Create */
                case 'create': $this->create(); break;

                /* Update */
                case 'update': $this->update(); break;

                /* Delete */
                case 'delete': $this->delete(); break;

            }

        }

        die();
    }

    private function create() {
        $_POST['name'] = trim(Database::clean_string($_POST['name']));
        $_POST['type'] = in_array($_POST['type'], require APP_PATH . 'includes/pixels.php') ? $_POST['type'] : '';
        $_POST['pixel'] = trim(Database::clean_string($_POST['pixel']));

        /* Check for possible errors */
        if(empty($_POST['name']) || empty($_POST['type']) || empty($_POST['pixel'])) {
            $errors[] = language()->global->error_message->empty_fields;
        }

        /* Make sure that the user didn't exceed the limit */
        $user_total_pixels = database()->query("SELECT COUNT(*) AS `total` FROM `pixels` WHERE `user_id` = {$this->user->user_id}")->fetch_object()->total;
        if($this->user->plan_settings->pixels_limit != -1 && $user_total_pixels >= $this->user->plan_settings->pixels_limit) {
            Response::json(language()->pixels->error_message->pixels_limit, 'error');
        }

        if(empty($errors)) {

            /* Insert to database */
            db()->insert('pixels', [
                'user_id' => $this->user->user_id,
                'type' => $_POST['type'],
                'name' => $_POST['name'],
                'pixel' => $_POST['pixel'],
                'datetime' => Date::$date,
            ]);

            Response::json(language()->pixel_create_modal->success_message, 'success');

        }
    }

    private function update() {
        $_POST['name'] = trim(Database::clean_string($_POST['name']));
        $_POST['type'] = in_array($_POST['type'], require APP_PATH . 'includes/pixels.php') ? $_POST['type'] : '';
        $_POST['pixel'] = trim(Database::clean_string($_POST['pixel']));

        /* Check for possible errors */
        if(empty($_POST['name']) || empty($_POST['type']) || empty($_POST['pixel'])) {
            $errors[] = language()->global->error_message->empty_fields;
        }

        if(empty($errors)) {

            /* Insert to database */
            db()->where('pixel_id', $_POST['pixel_id'])->where('user_id', $this->user->user_id)->update('pixels', [
                'type' => $_POST['type'],
                'name' => $_POST['name'],
                'pixel' => $_POST['pixel'],
                'last_datetime' => Date::$date,
            ]);

            Response::json(language()->pixel_update_modal->success_message, 'success');

        }
    }

    private function delete() {
        $_POST['pixel_id'] = (int) $_POST['pixel_id'];

        /* Delete from database */
        db()->where('pixel_id', $_POST['pixel_id'])->where('user_id', $this->user->user_id)->delete('pixels');

        Response::json(language()->pixel_delete_modal->success_message, 'success');

    }
}
