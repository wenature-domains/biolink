<?php

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Middlewares\Csrf;

class AdminLinks extends Controller {

    public function index() {

        /* Prepare the filtering system */
        $filters = (new \Altum\Filters(['is_enabled', 'user_id', 'project_id', 'domain_id', 'type'], ['url'], ['datetime', 'url', 'clicks']));

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `links` WHERE 1 = 1 {$filters->get_sql_where()}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('admin/links?' . $filters->get_get() . '&page=%d')));

        /* Get the users */
        $links = [];
        $links_result = database()->query("
            SELECT
                `links`.*, `users`.`name` AS `user_name`, `users`.`email` AS `user_email`, `domains`.`scheme`, `domains`.`host`
            FROM
                `links`
            LEFT JOIN
                `users` ON `links`.`user_id` = `users`.`user_id`
            LEFT JOIN
                `domains` ON `links`.`domain_id` = `domains`.`domain_id`
            WHERE
                1 = 1
                {$filters->get_sql_where('links')}
                {$filters->get_sql_order_by('links')}
            {$paginator->get_sql_limit()}
        ");
        while($row = $links_result->fetch_object()) {
            $links[] = $row;
        }

        /* Export handler */
        process_export_csv($links, 'include', ['link_id', 'user_id', 'project_id', 'pixels_ids', 'type', 'url', 'location_url', 'start_date', 'end_date', 'clicks', 'is_enabled', 'datetime'], sprintf(language()->admin_links->title));
        process_export_json($links, 'include', ['link_id', 'user_id', 'project_id', 'pixels_ids', 'type', 'url', 'location_url', 'settings', 'start_date', 'end_date', 'clicks', 'is_enabled', 'datetime'], sprintf(language()->admin_links->title));

        /* Prepare the pagination view */
        $pagination = (new \Altum\Views\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

        /* Delete Modal */
        $view = new \Altum\Views\View('admin/links/link_delete_modal', (array) $this);
        \Altum\Event::add_content($view->run(), 'modals');

        /* Main View */
        $data = [
            'links' => $links,
            'filters' => $filters,
            'pagination' => $pagination
        ];

        $view = new \Altum\Views\View('admin/links/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function delete() {

        $link_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        if(!Csrf::check('global_token')) {
            Alerts::add_error(language()->global->error_message->invalid_csrf_token);
            redirect('admin/links');
        }

        if(!$link = db()->where('link_id', $link_id)->getOne('links', ['link_id'])) {
            redirect('admin/links');
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            (new \Altum\Models\Link())->delete($link->link_id);

            /* Set a nice success message */
            Alerts::add_success(language()->admin_link_delete_modal->success_message);

        }

        redirect('admin/links');
    }

}
