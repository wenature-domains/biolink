<?php

namespace Altum\Controllers;

use Altum\Meta;

class Index extends Controller {

    public function index() {

        /* Custom index redirect if set */
        if(!empty(settings()->index_url)) {
            header('Location: ' . settings()->index_url);
            die();
        }

        /* Plans View */
        $data = [
            'simple_user_plan_settings' =>  require APP_PATH . 'includes/simple_user_plan_settings.php'
        ];

        $view = new \Altum\Views\View('partials/plans', (array) $this);

        $this->add_view_content('plans', $view->run($data));

        /* Opengraph image */
        if(settings()->opengraph) {
            Meta::set_social_url(SITE_URL);
            Meta::set_social_description(language()->index->meta_description);
            Meta::set_social_image(SITE_URL . UPLOADS_URL_PATH . 'opengraph/' .settings()->opengraph);
        }

        /* Main View */
        $data = [];

        $view = new \Altum\Views\View('index/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
