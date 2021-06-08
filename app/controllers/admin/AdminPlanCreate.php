<?php

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Database\Database;
use Altum\Middlewares\Csrf;

class AdminPlanCreate extends Controller {

    public function index() {

        if(in_array(settings()->license->type, ['Extended License', 'extended'])) {
            /* Get the available taxes from the system */
            $taxes = db()->get('taxes', null, ['tax_id', 'internal_name', 'name', 'description']);
        }

        if(!empty($_POST)) {
            /* Filter some the variables */
            $_POST['name'] = Database::clean_string($_POST['name']);
            $_POST['monthly_price'] = (float) $_POST['monthly_price'];
            $_POST['annual_price'] = (float) $_POST['annual_price'];
            $_POST['lifetime_price'] = (float) $_POST['lifetime_price'];

            /* Determine the enabled biolink blocks */
            $enabled_biolink_blocks = [];

            foreach(require APP_PATH . 'includes/biolink_blocks.php' as $biolink_block) {
                $enabled_biolink_blocks[$biolink_block] = (bool) isset($_POST['enabled_biolink_blocks']) && in_array($biolink_block, $_POST['enabled_biolink_blocks']);
            }

            $_POST['settings'] = json_encode([
                'additional_global_domains' => (bool) isset($_POST['additional_global_domains']),
                'custom_url' => (bool) isset($_POST['custom_url']),
                'deep_links' => (bool) isset($_POST['deep_links']),
                'no_ads' => (bool) isset($_POST['no_ads']),
                'removable_branding' => (bool) isset($_POST['removable_branding']),
                'custom_branding' => (bool) isset($_POST['custom_branding']),
                'custom_colored_links' => (bool) isset($_POST['custom_colored_links']),
                'statistics' => (bool) isset($_POST['statistics']),
                'custom_backgrounds' => (bool) isset($_POST['custom_backgrounds']),
                'verified' => (bool) isset($_POST['verified']),
                'temporary_url_is_enabled' => (bool) isset($_POST['temporary_url_is_enabled']),
                'seo' => (bool) isset($_POST['seo']),
                'utm' => (bool) isset($_POST['utm']),
                'socials' => (bool) isset($_POST['socials']),
                'fonts' => (bool) isset($_POST['fonts']),
                'password' => (bool) isset($_POST['password']),
                'sensitive_content' => (bool) isset($_POST['sensitive_content']),
                'leap_link' => (bool) isset($_POST['leap_link']),
                'api_is_enabled' => (bool) isset($_POST['api_is_enabled']),
                'projects_limit' => (int) $_POST['projects_limit'],
                'pixels_limit' => (int) $_POST['pixels_limit'],
                'biolinks_limit' => (int) $_POST['biolinks_limit'],
                'links_limit' => (int) $_POST['links_limit'],
                'domains_limit' => (int) $_POST['domains_limit'],
                'enabled_biolink_blocks' => $enabled_biolink_blocks,
            ]);

            $_POST['status'] = (int) $_POST['status'];
            $_POST['taxes_ids'] = json_encode(array_keys($_POST['taxes_ids'] ?? []));

            if(!Csrf::check()) {
                Alerts::add_error(language()->global->error_message->invalid_csrf_token);
            }

            if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

                /* Database query */
                db()->insert('plans', [
                    'name' => $_POST['name'],
                    'monthly_price' => $_POST['monthly_price'],
                    'annual_price' => $_POST['annual_price'],
                    'lifetime_price' => $_POST['lifetime_price'],
                    'settings' => $_POST['settings'],
                    'taxes_ids' => $_POST['taxes_ids'],
                    'status' => $_POST['status'],
                    'date' => \Altum\Date::$date,
                ]);

                /* Set a nice success message */
                Alerts::add_success(language()->global->success_message->basic);

                redirect('admin/plans');
            }
        }


        /* Main View */
        $data = [
            'taxes' => $taxes ?? null
        ];

        $view = new \Altum\Views\View('admin/plan-create/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
