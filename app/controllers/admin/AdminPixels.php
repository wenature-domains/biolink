<?php

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Middlewares\Csrf;

class AdminPixels extends Controller {

    public function index() {

        /* Prepare the filtering system */
        $filters = (new \Altum\Filters(['user_id', 'type'], ['name'], ['name', 'datetime']));

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `pixels` WHERE 1 = 1 {$filters->get_sql_where()}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('admin/pixels?' . $filters->get_get() . '&page=%d')));

        /* Get the data */
        $pixels = [];
        $pixels_result = database()->query("
            SELECT
                `pixels`.*, `users`.`name` AS `user_name`, `users`.`email` AS `user_email`
            FROM
                `pixels`
            LEFT JOIN
                `users` ON `pixels`.`user_id` = `users`.`user_id`
            WHERE
                1 = 1
                {$filters->get_sql_where('pixels')}
                {$filters->get_sql_order_by('pixels')}

            {$paginator->get_sql_limit()}
        ");
        while($row = $pixels_result->fetch_object()) {
            $pixels[] = $row;
        }

        /* Export handler */
        process_export_csv($pixels, 'include', ['pixel_id', 'user_id', 'type', 'name', 'pixel', 'last_datetime', 'datetime'], sprintf(language()->admin_pixels->title));
        process_export_json($pixels, 'include', ['pixel_id', 'user_id', 'type', 'name', 'pixel', 'last_datetime', 'datetime'], sprintf(language()->admin_pixels->title));

        /* Prepare the pagination view */
        $pagination = (new \Altum\Views\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

        /* Delete Modal */
        $view = new \Altum\Views\View('admin/pixels/pixel_delete_modal', (array) $this);
        \Altum\Event::add_content($view->run(), 'modals');

        /* Main View */
        $data = [
            'pixels' => $pixels,
            'filters' => $filters,
            'pagination' => $pagination
        ];

        $view = new \Altum\Views\View('admin/pixels/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function delete() {

        $pixel_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        if(!Csrf::check('global_token')) {
            Alerts::add_error(language()->global->error_message->invalid_csrf_token);
        }

        if(!$pixel = db()->where('pixel_id', $pixel_id)->getOne('pixels', ['pixel_id'])) {
            redirect('admin/pixels');
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            /* Delete the pixel */
            db()->where('pixel_id', $pixel->pixel_id)->delete('pixels');

            /* Clear the cache */
            \Altum\Cache::$adapter->deleteItemsByTag('pixel_id=' . $pixel->pixel_id);

            /* Set a nice success message */
            Alerts::add_success(language()->admin_pixel_delete_modal->success_message);

        }

        redirect('admin/pixels');
    }

}
