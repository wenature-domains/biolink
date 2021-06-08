<?php

namespace Altum\Controllers;

use Altum\Database\Database;
use Altum\Date;
use Altum\Middlewares\Authentication;
use Altum\Middlewares\Csrf;
use Altum\Response;
use Altum\Routing\Router;

class BiolinkBlockAjax extends Controller {

    public function index() {
        Authentication::guard();

        if(!empty($_POST) && (Csrf::check('token') || Csrf::check('global_token')) && isset($_POST['request_type'])) {

            switch($_POST['request_type']) {

                /* Status toggle */
                case 'is_enabled_toggle': $this->is_enabled_toggle(); break;

                /* Duplicate link */
                case 'duplicate': $this->duplicate(); break;

                /* Order links */
                case 'order': $this->order(); break;

                /* Create */
                case 'create': $this->create(); break;

                /* Update */
                case 'update': $this->update(); break;

                /* Delete */
                case 'delete': $this->delete(); break;

            }

        }

        die($_POST['request_type']);
    }

    private function is_enabled_toggle() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];

        /* Get the current status */
        $biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks', ['biolink_block_id', 'is_enabled']);

        if($biolink_block) {
            $new_is_enabled = (int) !$biolink_block->is_enabled;

            db()->where('biolink_block_id', $biolink_block->biolink_block_id)->update('biolinks_blocks', ['is_enabled' => $new_is_enabled]);

            /* Clear the cache */
            \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);
//            \Altum\Cache::$adapter->deleteItemsByTag('link_id=' . $_POST['link_id']);

            Response::json('', 'success');
        }
    }

    private function duplicate() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];

        /* Get the link data */
        $biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks');

        if($biolink_block) {
            $biolink_block->settings = json_decode($biolink_block->settings);

            $settings = json_encode([
                'name' => $biolink_block->settings->name,
                'image' => $biolink_block->settings->image,
                'text_color' => $biolink_block->settings->text_color,
                'background_color' => $biolink_block->settings->background_color,
                'outline' => $biolink_block->settings->outline,
                'border_radius' => $biolink_block->settings->border_radius,
                'animation' => $biolink_block->settings->animation,
                'animation_runs' => $biolink_block->settings->animation_runs,
                'icon' => $biolink_block->settings->icon
            ]);

            /* Database query */
            db()->insert('biolinks_blocks', [
                'user_id' => $this->user->user_id,
                'link_id' => $biolink_block->link_id,
                'type' => $biolink_block->type,
                'location_url' => $biolink_block->location_url,
                'settings' => $settings,
                'start_date' => $biolink_block->start_date,
                'end_date' => $biolink_block->end_date,
                'is_enabled' => $biolink_block->is_enabled,
                'datetime' => \Altum\Date::$date,
            ]);

            /* Clear the cache */
            \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);

            Response::json('', 'success', ['url' => url('link/' . $biolink_block->link_id . '?tab=links')]);

        }
    }

    private function order() {

        if(isset($_POST['biolink_blocks']) && is_array($_POST['biolink_blocks'])) {
            foreach($_POST['biolink_blocks'] as $link) {
                $link['link_id'] = (int) $link['link_id'];
                $link['order'] = (int) $link['order'];

                /* Update the link order */
                db()->where('biolink_block_id', $link['biolink_block_id'])->where('user_id', $this->user->user_id)->update('biolinks_blocks', ['order' => $link['order']]);

            }
        }

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);

        Response::json('', 'success');
    }

    private function create() {

        /* Check for available biolink blocks */
        if(isset($_POST['type']) && in_array($_POST['type'], require APP_PATH . 'includes/biolink_blocks.php')) {
            $_POST['type'] = trim(Database::clean_string($_POST['type']));

            if(in_array($_POST['type'], ['link', 'mail', 'rss_feed', 'custom_html', 'vcard', 'text', 'image', 'image_grid', 'divider'])) {
                $this->{'create_biolink_' . $_POST['type']}();
            } else {
                $this->create_biolink_other($_POST['type']);
            }

        }

        die();
    }

    private function create_biolink_link() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = trim(Database::clean_string($_POST['location_url']));
        $_POST['name'] = trim(Database::clean_string($_POST['name']));

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url']);

        $type = 'link';
        $settings = json_encode([
            'name' => $_POST['name'],
            'text_color' => 'black',
            'background_color' => 'white',
            'outline' => false,
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',
            'image' => '',
        ]);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'datetime' => \Altum\Date::$date,
        ]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=links')]);
    }

    private function create_biolink_other($type) {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = trim(Database::clean_string($_POST['location_url']));

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url']);

        $settings = json_encode([]);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'datetime' => \Altum\Date::$date,
        ]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=links')]);
    }

    private function create_biolink_mail() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['name'] = trim(Database::clean_string($_POST['name']));

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'mail';
        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => '',
            'text_color' => 'black',
            'background_color' => 'white',
            'outline' => false,
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',

            'email_placeholder' => language()->link->biolink->mail->email_placeholder_default,
            'name_placeholder' => language()->link->biolink->mail->name_placeholder_default,
            'button_text' => language()->link->biolink->mail->button_text_default,
            'success_text' => language()->link->biolink->mail->success_text_default,
            'show_agreement' => false,
            'agreement_url' => '',
            'agreement_text' => '',
            'mailchimp_api' => '',
            'mailchimp_api_list' => '',
            'webhook_url' => ''
        ]);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'datetime' => \Altum\Date::$date,
        ]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=links')]);
    }

    private function create_biolink_rss_feed() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = trim(Database::clean_string($_POST['location_url']));

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url']);

        $type = 'rss_feed';
        $settings = json_encode([
            'amount' => 5,
            'text_color' => 'black',
            'background_color' => 'white',
            'outline' => false,
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
        ]);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'datetime' => \Altum\Date::$date,
        ]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=links')]);
    }

    private function create_biolink_custom_html() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['html'] = trim($_POST['html']);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'custom_html';
        $settings = json_encode([
            'html' => $_POST['html']
        ]);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'datetime' => \Altum\Date::$date,
        ]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=links')]);
    }

    private function create_biolink_vcard() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['name'] = trim(Database::clean_string($_POST['name']));

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'vcard';
        $settings = [
            'name' => $_POST['name'],
            'image' => '',
            'first_name' => '',
            'last_name' => '',
            'text_color' => 'black',
            'background_color' => 'white',
            'outline' => false,
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',
        ];
        foreach(['first_name', 'last_name', 'phone', 'street', 'city', 'zip', 'region', 'country', 'email', 'website', 'note'] as $key) {
            $settings[$key] = '';
        }
        $settings = json_encode($settings);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'datetime' => \Altum\Date::$date,
        ]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=links')]);
    }

    private function create_biolink_text() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['title'] = trim(Database::clean_string($_POST['title']));
        $_POST['description'] = trim(Database::clean_string($_POST['description']));

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'text';
        $settings = json_encode([
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'title_text_color' => 'white',
            'description_text_color' => 'white',
        ]);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'datetime' => \Altum\Date::$date,
        ]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=links')]);
    }

    private function create_biolink_image() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = trim(Database::clean_string($_POST['location_url']));

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url'], true);

        /* Image upload */
        $image_allowed_extensions = ['jpg', 'jpeg', 'png', 'svg', 'ico', 'gif'];
        $image = (bool) !empty($_FILES['image']['name']);

        if(!$image) {
            Response::json(language()->global->error_message->empty_fields, 'error');
        }

        $image_file_extension = explode('.', $_FILES['image']['name']);
        $image_file_extension = mb_strtolower(end($image_file_extension));
        $image_file_temp = $_FILES['image']['tmp_name'];

        if(!is_writable(UPLOADS_PATH . 'block_images/')) {
            Response::json(sprintf(language()->global->error_message->directory_not_writable, UPLOADS_PATH . 'block_images/'), 'error');
        }

        if($_FILES['image']['error']) {
            Response::json(language()->global->error_message->file_upload, 'error');
        }

        if(!in_array($image_file_extension, $image_allowed_extensions)) {
            Response::json(language()->global->error_message->invalid_file_type, 'error');
        }

        if($_FILES['image']['size'] > settings()->links->image_size_limit * 1000000) {
            Response::json(sprintf(language()->global->error_message->file_size_limit, settings()->links->image_size_limit), 'error');
        }

        /* Generate new name for the image */
        $image_new_name = md5(time() . rand()) . '.' . $image_file_extension;

        /* Upload the original */
        move_uploaded_file($image_file_temp, UPLOADS_PATH . 'block_images/' . $image_new_name);

        $type = 'image';
        $settings = json_encode([
            'image' => $image_new_name,
        ]);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'datetime' => \Altum\Date::$date,
        ]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=links')]);
    }

    private function create_biolink_image_grid() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['name'] = trim(Database::clean_string($_POST['name']));
        $_POST['location_url'] = trim(Database::clean_string($_POST['location_url']));

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url'], true);

        /* Image upload */
        $image_allowed_extensions = ['jpg', 'jpeg', 'png', 'svg', 'ico', 'gif'];
        $image = (bool) !empty($_FILES['image']['name']);

        if(!$image) {
            Response::json(language()->global->error_message->empty_fields, 'error');
        }

        $image_file_extension = explode('.', $_FILES['image']['name']);
        $image_file_extension = mb_strtolower(end($image_file_extension));
        $image_file_temp = $_FILES['image']['tmp_name'];

        if(!is_writable(UPLOADS_PATH . 'block_images/')) {
            Response::json(sprintf(language()->global->error_message->directory_not_writable, UPLOADS_PATH . 'block_images/'), 'error');
        }

        if($_FILES['image']['error']) {
            Response::json(language()->global->error_message->file_upload, 'error');
        }

        if(!in_array($image_file_extension, $image_allowed_extensions)) {
            Response::json(language()->global->error_message->invalid_file_type, 'error');
        }

        if($_FILES['image']['size'] > settings()->links->image_size_limit * 1000000) {
            Response::json(sprintf(language()->global->error_message->file_size_limit, settings()->links->image_size_limit), 'error');
        }

        /* Generate new name for the image */
        $image_new_name = md5(time() . rand()) . '.' . $image_file_extension;

        /* Upload the original */
        move_uploaded_file($image_file_temp, UPLOADS_PATH . 'block_images/' . $image_new_name);

        $type = 'image_grid';
        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => $image_new_name,
        ]);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'datetime' => \Altum\Date::$date,
        ]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=links')]);
    }

    private function create_biolink_divider() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['margin_top'] = $_POST['margin_top'] > 7 || $_POST['margin_top'] < 0 ? 3 : (int) $_POST['margin_top'];
        $_POST['margin_bottom'] = $_POST['margin_bottom'] > 7 || $_POST['margin_bottom'] < 0 ? 3 : (int) $_POST['margin_bottom'];

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'divider';
        $settings = json_encode([
            'margin_top' => $_POST['margin_top'],
            'margin_bottom' => $_POST['margin_bottom'],
            'background_color' => 'white',
            'icon' => 'fa fa-infinity'
        ]);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'datetime' => \Altum\Date::$date,
        ]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=links')]);
    }

    private function update() {

        if(!empty($_POST)) {
            /* Check for available biolink blocks */
            if(isset($_POST['type']) && in_array($_POST['type'], require APP_PATH . 'includes/biolink_blocks.php')) {
                $_POST['type'] = trim(Database::clean_string($_POST['type']));

                if(in_array($_POST['type'], ['link', 'mail', 'rss_feed', 'custom_html', 'vcard', 'text', 'image', 'image_grid', 'divider'])) {
                    $this->{'update_biolink_' . $_POST['type']}();
                } else {
                    $this->update_biolink_other($_POST['type']);
                }

            }
        }

        die();
    }

    private function update_biolink_link() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['location_url'] = trim(Database::clean_string($_POST['location_url']));
        $_POST['name'] = trim(Database::clean_string($_POST['name']));
        $_POST['outline'] = (bool) isset($_POST['outline']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? Database::clean_string($_POST['border_radius']) : 'rounded';
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? Database::clean_string($_POST['animation']) : false;
        $_POST['animation_runs'] = in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? Database::clean_string($_POST['animation_runs']) : false;
        $_POST['icon'] = trim(Database::clean_string($_POST['icon']));
        $_POST['text_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['text_color']) ? '#000' : $_POST['text_color'];
        $_POST['background_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['background_color']) ? '#fff' : $_POST['background_color'];
        if(isset($_POST['schedule']) && !empty($_POST['start_date']) && !empty($_POST['end_date']) && Date::validate($_POST['start_date'], 'Y-m-d H:i:s') && Date::validate($_POST['end_date'], 'Y-m-d H:i:s')) {
            $_POST['start_date'] = (new \DateTime($_POST['start_date'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s');
            $_POST['end_date'] = (new \DateTime($_POST['end_date'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s');
        } else {
            $_POST['start_date'] = $_POST['end_date'] = null;
        }

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }
        $biolink_block->settings = json_decode($biolink_block->settings);

        /* Check for any errors */
        $required_fields = ['location_url', 'name'];

        /* Check for any errors */
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || (isset($_POST[$field]) && empty($_POST[$field]))) {
                Response::json(language()->global->error_message->empty_fields, 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url']);

        /* Image upload */
        $image_allowed_extensions = ['jpg', 'jpeg', 'png', 'svg', 'ico', 'gif'];
        $image = (bool) !empty($_FILES['image']['name']) && !isset($_POST['image_remove']);
        $db_image = $biolink_block->settings->image;

        if($image) {
            $image_file_extension = explode('.', $_FILES['image']['name']);
            $image_file_extension = mb_strtolower(end($image_file_extension));
            $image_file_temp = $_FILES['image']['tmp_name'];

            if(!is_writable(UPLOADS_PATH . 'block_thumbnail_images/')) {
                Response::json(sprintf(language()->global->error_message->directory_not_writable, UPLOADS_PATH . 'block_thumbnail_images/'), 'error');
            }

            if($_FILES['image']['error']) {
                Response::json(language()->global->error_message->file_upload, 'error');
            }

            if(!in_array($image_file_extension, $image_allowed_extensions)) {
                Response::json(language()->global->error_message->invalid_file_type, 'error');
            }

            if($_FILES['image']['size'] > settings()->links->thumbnail_image_size_limit * 1000000) {
                Response::json(sprintf(language()->global->error_message->file_size_limit, settings()->links->thumbnail_image_size_limit), 'error');
            }

            /* Delete current image */
            if(!empty($biolink_block->settings->image) && file_exists(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image)) {
                unlink(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image);
            }

            /* Generate new name for the image */
            $image_new_name = md5(time() . rand()) . '.' . $image_file_extension;

            /* Upload the original */
            move_uploaded_file($image_file_temp, UPLOADS_PATH . 'block_thumbnail_images/' . $image_new_name);

            $db_image = $image_new_name;
        }

        /* Check for the removal of the already uploaded file */
        if(isset($_POST['image_remove'])) {
            /* Delete current file */
            if(!empty($biolink_block->settings->image) && file_exists(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image)) {
                unlink(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image);
            }
            $db_image = null;
        }

        $image_url = $db_image ? SITE_URL . UPLOADS_URL_PATH . 'block_thumbnail_images/' . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'text_color' => $_POST['text_color'],
            'background_color' => $_POST['background_color'],
            'outline' => $_POST['outline'],
            'border_radius' => $_POST['border_radius'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'image' => $db_image,
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
        ]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);
        \Altum\Cache::$adapter->deleteItemsByTag('link_id=' . $biolink_block->link_id);

        Response::json(language()->link->success_message->settings_updated, 'success', ['image_prop' => true, 'image_url' => $image_url]);
    }

    private function update_biolink_other($type) {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['location_url'] = trim(Database::clean_string($_POST['location_url']));

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $this->check_location_url($_POST['location_url']);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
        ]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);
        \Altum\Cache::$adapter->deleteItemsByTag('link_id=' . $biolink_block->link_id);

        Response::json(language()->link->success_message->settings_updated, 'success');
    }

    private function update_biolink_mail() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['name'] = trim(Database::clean_string($_POST['name']));
        $_POST['url'] = !empty($_POST['url']) ? get_slug(Database::clean_string($_POST['url']), '-', false) : false;
        $_POST['outline'] = (bool) isset($_POST['outline']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? Database::clean_string($_POST['border_radius']) : 'rounded';
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? Database::clean_string($_POST['animation']) : false;
        $_POST['animation_runs'] = in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? Database::clean_string($_POST['animation_runs']) : false;
        $_POST['icon'] = trim(Database::clean_string($_POST['icon']));
        $_POST['text_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['text_color']) ? '#000' : $_POST['text_color'];
        $_POST['background_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['background_color']) ? '#fff' : $_POST['background_color'];
        $_POST['email_placeholder'] = trim(Database::clean_string($_POST['email_placeholder']));
        $_POST['name_placeholder'] = trim(Database::clean_string($_POST['name_placeholder']));
        $_POST['button_text'] = trim(Database::clean_string($_POST['button_text']));
        $_POST['success_text'] = trim(Database::clean_string($_POST['success_text']));
        $_POST['show_agreement'] = (bool) isset($_POST['show_agreement']);
        $_POST['agreement_url'] = trim(Database::clean_string($_POST['agreement_url']));
        $_POST['agreement_text'] = trim(Database::clean_string($_POST['agreement_text']));
        $_POST['mailchimp_api'] = trim(Database::clean_string($_POST['mailchimp_api']));
        $_POST['mailchimp_api_list'] = trim(Database::clean_string($_POST['mailchimp_api_list']));
        $_POST['webhook_url'] = trim(Database::clean_string($_POST['webhook_url']));

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }
        $biolink_block->settings = json_decode($biolink_block->settings);

        /* Image upload */
        $image_allowed_extensions = ['jpg', 'jpeg', 'png', 'svg', 'ico', 'gif'];
        $image = (bool) !empty($_FILES['image']['name']) && !isset($_POST['image_remove']);
        $db_image = $biolink_block->settings->image;

        if($image) {
            $image_file_extension = explode('.', $_FILES['image']['name']);
            $image_file_extension = mb_strtolower(end($image_file_extension));
            $image_file_temp = $_FILES['image']['tmp_name'];

            if(!is_writable(UPLOADS_PATH . 'block_thumbnail_images/')) {
                Response::json(sprintf(language()->global->error_message->directory_not_writable, UPLOADS_PATH . 'block_thumbnail_images/'), 'error');
            }

            if($_FILES['image']['error']) {
                Response::json(language()->global->error_message->file_upload, 'error');
            }

            if(!in_array($image_file_extension, $image_allowed_extensions)) {
                Response::json(language()->global->error_message->invalid_file_type, 'error');
            }

            if($_FILES['image']['size'] > settings()->links->thumbnail_image_size_limit * 1000000) {
                Response::json(sprintf(language()->global->error_message->file_size_limit, settings()->links->thumbnail_image_size_limit), 'error');
            }

            /* Delete current image */
            if(!empty($biolink_block->settings->image) && file_exists(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image)) {
                unlink(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image);
            }

            /* Generate new name for the image */
            $image_new_name = md5(time() . rand()) . '.' . $image_file_extension;

            /* Upload the original */
            move_uploaded_file($image_file_temp, UPLOADS_PATH . 'block_thumbnail_images/' . $image_new_name);

            $db_image = $image_new_name;
        }

        /* Check for the removal of the already uploaded file */
        if(isset($_POST['image_remove'])) {
            /* Delete current file */
            if(!empty($biolink_block->settings->image) && file_exists(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image)) {
                unlink(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image);
            }
            $db_image = null;
        }

        $image_url = $db_image ? SITE_URL . UPLOADS_URL_PATH . 'block_thumbnail_images/' . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => $db_image,
            'text_color' => $_POST['text_color'],
            'background_color' => $_POST['background_color'],
            'outline' => $_POST['outline'],
            'border_radius' => $_POST['border_radius'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'email_placeholder' => $_POST['email_placeholder'],
            'name_placeholder' => $_POST['name_placeholder'],
            'button_text' => $_POST['button_text'],
            'success_text' => $_POST['success_text'],
            'show_agreement' => $_POST['show_agreement'],
            'agreement_url' => $_POST['agreement_url'],
            'agreement_text' => $_POST['agreement_text'],
            'mailchimp_api' => $_POST['mailchimp_api'],
            'mailchimp_api_list' => $_POST['mailchimp_api_list'],
            'webhook_url' => $_POST['webhook_url']
        ]);

        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', ['settings' => $settings]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);
        \Altum\Cache::$adapter->deleteItemsByTag('link_id=' . $biolink_block->link_id);

        Response::json(language()->link->success_message->settings_updated, 'success', ['image_prop' => true, 'image_url' => $image_url]);
    }

    private function update_biolink_rss_feed() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['location_url'] = trim(Database::clean_string($_POST['location_url']));
        $_POST['amount'] = (int) Database::clean_string($_POST['amount']);
        $_POST['outline'] = (bool) isset($_POST['outline']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? Database::clean_string($_POST['border_radius']) : 'rounded';
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? Database::clean_string($_POST['animation']) : false;
        $_POST['animation_runs'] = in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? Database::clean_string($_POST['animation_runs']) : false;
        $_POST['text_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['text_color']) ? '#000' : $_POST['text_color'];
        $_POST['background_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['background_color']) ? '#fff' : $_POST['background_color'];

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $this->check_location_url($_POST['location_url']);

        $settings = json_encode([
            'amount' => $_POST['amount'],
            'text_color' => $_POST['text_color'],
            'background_color' => $_POST['background_color'],
            'outline' => $_POST['outline'],
            'border_radius' => $_POST['border_radius'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
        ]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);
        \Altum\Cache::$adapter->deleteItemsByTag('link_id=' . $biolink_block->link_id);

        Response::json(language()->link->success_message->settings_updated, 'success');
    }

    private function update_biolink_custom_html() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['html'] = trim($_POST['html']);

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
            'html' => $_POST['html'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
        ]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);
        \Altum\Cache::$adapter->deleteItemsByTag('link_id=' . $biolink_block->link_id);

        Response::json(language()->link->success_message->settings_updated, 'success');
    }

    private function update_biolink_vcard() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['name'] = trim(Database::clean_string($_POST['name']));
        $_POST['outline'] = (bool) isset($_POST['outline']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? Database::clean_string($_POST['border_radius']) : 'rounded';
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? Database::clean_string($_POST['animation']) : false;
        $_POST['animation_runs'] = in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? Database::clean_string($_POST['animation_runs']) : false;
        $_POST['text_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['text_color']) ? '#000' : $_POST['text_color'];
        $_POST['background_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['background_color']) ? '#fff' : $_POST['background_color'];
        $_POST['icon'] = trim(Database::clean_string($_POST['icon']));
        foreach(['first_name', 'last_name', 'phone', 'street', 'city', 'zip', 'region', 'country', 'email', 'website', 'company', 'note'] as $key) {
            $_POST[$key] = trim(Database::clean_string($_POST[$key]));
        }

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }
        $biolink_block->settings = json_decode($biolink_block->settings);

        /* Image upload */
        $image_allowed_extensions = ['jpg', 'jpeg', 'png', 'svg', 'ico', 'gif'];
        $image = (bool) !empty($_FILES['image']['name']) && !isset($_POST['image_remove']);
        $db_image = $biolink_block->settings->image;

        if($image) {
            $image_file_extension = explode('.', $_FILES['image']['name']);
            $image_file_extension = mb_strtolower(end($image_file_extension));
            $image_file_temp = $_FILES['image']['tmp_name'];

            if(!is_writable(UPLOADS_PATH . 'block_thumbnail_images/')) {
                Response::json(sprintf(language()->global->error_message->directory_not_writable, UPLOADS_PATH . 'block_thumbnail_images/'), 'error');
            }

            if($_FILES['image']['error']) {
                Response::json(language()->global->error_message->file_upload, 'error');
            }

            if(!in_array($image_file_extension, $image_allowed_extensions)) {
                Response::json(language()->global->error_message->invalid_file_type, 'error');
            }

            if($_FILES['image']['size'] > settings()->links->thumbnail_image_size_limit * 1000000) {
                Response::json(sprintf(language()->global->error_message->file_size_limit, settings()->links->thumbnail_image_size_limit), 'error');
            }

            /* Delete current image */
            if(!empty($biolink_block->settings->image) && file_exists(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image)) {
                unlink(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image);
            }

            /* Generate new name for the image */
            $image_new_name = md5(time() . rand()) . '.' . $image_file_extension;

            /* Upload the original */
            move_uploaded_file($image_file_temp, UPLOADS_PATH . 'block_thumbnail_images/' . $image_new_name);

            $db_image = $image_new_name;
        }

        /* Check for the removal of the already uploaded file */
        if(isset($_POST['image_remove'])) {
            /* Delete current file */
            if(!empty($biolink_block->settings->image) && file_exists(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image)) {
                unlink(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image);
            }
            $db_image = null;
        }

        $image_url = $db_image ? SITE_URL . UPLOADS_URL_PATH . 'block_thumbnail_images/' . $db_image : null;

        $settings = [
            'name' => $_POST['name'],
            'image' => $db_image,
            'text_color' => $_POST['text_color'],
            'background_color' => $_POST['background_color'],
            'outline' => $_POST['outline'],
            'border_radius' => $_POST['border_radius'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
        ];

        foreach(['first_name', 'last_name', 'phone', 'street', 'city', 'zip', 'region', 'country', 'email', 'website', 'company', 'note'] as $key) {
            $settings[$key] = $_POST[$key];
        }

        $settings = json_encode($settings);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
        ]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);
        \Altum\Cache::$adapter->deleteItemsByTag('link_id=' . $biolink_block->link_id);

        Response::json(language()->link->success_message->settings_updated, 'success', ['image_prop' => true, 'image_url' => $image_url]);
    }

    private function update_biolink_text() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['title'] = trim(Database::clean_string($_POST['title']));
        $_POST['description'] = trim(filter_var(strip_tags($_POST['description']), FILTER_SANITIZE_STRING));
        $_POST['title_text_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['title_text_color']) ? '#fff' : $_POST['title_text_color'];
        $_POST['description_text_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['description_text_color']) ? '#fff' : $_POST['description_text_color'];

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'title_text_color' => $_POST['title_text_color'],
            'description_text_color' => $_POST['description_text_color'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
        ]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);
        \Altum\Cache::$adapter->deleteItemsByTag('link_id=' . $biolink_block->link_id);

        Response::json(language()->link->success_message->settings_updated, 'success');
    }

    private function update_biolink_image() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['location_url'] = trim(Database::clean_string($_POST['location_url']));

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }
        $biolink_block->settings = json_decode($biolink_block->settings);

        $this->check_location_url($_POST['location_url'], true);

        /* Image upload */
        $image_allowed_extensions = ['jpg', 'jpeg', 'png', 'svg', 'ico', 'gif'];
        $image = (bool) !empty($_FILES['image']['name']);
        $db_image = $biolink_block->settings->image;

        if($image) {
            $image_file_extension = explode('.', $_FILES['image']['name']);
            $image_file_extension = mb_strtolower(end($image_file_extension));
            $image_file_temp = $_FILES['image']['tmp_name'];

            if(!is_writable(UPLOADS_PATH . 'block_images/')) {
                Response::json(sprintf(language()->global->error_message->directory_not_writable, UPLOADS_PATH . 'block_images/'), 'error');
            }

            if($_FILES['image']['error']) {
                Response::json(language()->global->error_message->file_upload, 'error');
            }

            if(!in_array($image_file_extension, $image_allowed_extensions)) {
                Response::json(language()->global->error_message->invalid_file_type, 'error');
            }

            if($_FILES['image']['size'] > settings()->links->image_size_limit * 1000000) {
                Response::json(sprintf(language()->global->error_message->file_size_limit, settings()->links->image_size_limit), 'error');
            }

            /* Delete current image */
            if(!empty($biolink_block->settings->image) && file_exists(UPLOADS_PATH . 'block_images/' . $biolink_block->settings->image)) {
                unlink(UPLOADS_PATH . 'block_images/' . $biolink_block->settings->image);
            }

            /* Generate new name for the image */
            $image_new_name = md5(time() . rand()) . '.' . $image_file_extension;

            /* Upload the original */
            move_uploaded_file($image_file_temp, UPLOADS_PATH . 'block_images/' . $image_new_name);

            $db_image = $image_new_name;
        }

        $image_url = $db_image ? SITE_URL . UPLOADS_URL_PATH . 'block_images/' . $db_image : null;

        $settings = json_encode([
            'image' => $db_image,
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
        ]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);
        \Altum\Cache::$adapter->deleteItemsByTag('link_id=' . $biolink_block->link_id);

        Response::json(language()->link->success_message->settings_updated, 'success', ['image_prop' => true, 'image_url' => $image_url]);
    }

    private function update_biolink_image_grid() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['name'] = trim(Database::clean_string($_POST['name']));
        $_POST['location_url'] = trim(Database::clean_string($_POST['location_url']));

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }
        $biolink_block->settings = json_decode($biolink_block->settings);

        $this->check_location_url($_POST['location_url'], true);

        /* Image upload */
        $image_allowed_extensions = ['jpg', 'jpeg', 'png', 'svg', 'ico', 'gif'];
        $image = (bool) !empty($_FILES['image']['name']);
        $db_image = $biolink_block->settings->image;

        if($image) {
            $image_file_extension = explode('.', $_FILES['image']['name']);
            $image_file_extension = mb_strtolower(end($image_file_extension));
            $image_file_temp = $_FILES['image']['tmp_name'];

            if(!is_writable(UPLOADS_PATH . 'block_images/')) {
                Response::json(sprintf(language()->global->error_message->directory_not_writable, UPLOADS_PATH . 'block_images/'), 'error');
            }

            if($_FILES['image']['error']) {
                Response::json(language()->global->error_message->file_upload, 'error');
            }

            if(!in_array($image_file_extension, $image_allowed_extensions)) {
                Response::json(language()->global->error_message->invalid_file_type, 'error');
            }

            if($_FILES['image']['size'] > settings()->links->image_size_limit * 1000000) {
                Response::json(sprintf(language()->global->error_message->file_size_limit, settings()->links->image_size_limit), 'error');
            }

            /* Delete current image */
            if(!empty($biolink_block->settings->image) && file_exists(UPLOADS_PATH . 'block_images/' . $biolink_block->settings->image)) {
                unlink(UPLOADS_PATH . 'block_images/' . $biolink_block->settings->image);
            }

            /* Generate new name for the image */
            $image_new_name = md5(time() . rand()) . '.' . $image_file_extension;

            /* Upload the original */
            move_uploaded_file($image_file_temp, UPLOADS_PATH . 'block_images/' . $image_new_name);

            $db_image = $image_new_name;
        }

        $image_url = $db_image ? SITE_URL . UPLOADS_URL_PATH . 'block_images/' . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => $db_image,
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
        ]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);
        \Altum\Cache::$adapter->deleteItemsByTag('link_id=' . $biolink_block->link_id);

        Response::json(language()->link->success_message->settings_updated, 'success', ['image_prop' => true, 'image_url' => $image_url]);
    }

    private function update_biolink_divider() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['margin_top'] = $_POST['margin_top'] > 7 || $_POST['margin_top'] < 0 ? 3 : (int) $_POST['margin_top'];
        $_POST['margin_bottom'] = $_POST['margin_bottom'] > 7 || $_POST['margin_bottom'] < 0 ? 3 : (int) $_POST['margin_bottom'];
        $_POST['background_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['background_color']) ? '#fff' : $_POST['background_color'];
        $_POST['icon'] = trim(Database::clean_string($_POST['icon']));

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
            'margin_top' => $_POST['margin_top'],
            'margin_bottom' => $_POST['margin_bottom'],
            'background_color' => $_POST['background_color'],
            'icon' => $_POST['icon'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
        ]);

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $this->user->user_id);
        \Altum\Cache::$adapter->deleteItemsByTag('link_id=' . $biolink_block->link_id);

        Response::json(language()->link->success_message->settings_updated, 'success');
    }

    private function delete() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];

        /* Check for possible errors */
        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        (new \Altum\Models\BiolinkBlock())->delete($biolink_block->biolink_block_id);

        Response::json('', 'success', ['url' => url('link/' . $biolink_block->link_id . '?tab=links')]);
    }

    /* Function to bundle together all the checks of an url */
    private function check_location_url($url, $can_be_empty = false) {

        if(empty(trim($url)) && $can_be_empty) {
            return;
        }

        if(empty(trim($url))) {
            Response::json(language()->global->error_message->empty_fields, 'error');
        }

        $url_details = parse_url($url);

        if(!isset($url_details['scheme'])) {
            Response::json(language()->link->error_message->invalid_location_url, 'error');
        }

        if(!$this->user->plan_settings->deep_links && !in_array($url_details['scheme'], ['http', 'https'])) {
            Response::json(language()->link->error_message->invalid_location_url, 'error');
        }

        /* Make sure the domain is not blacklisted */
        if(in_array(mb_strtolower(get_domain($url)), explode(',', settings()->links->blacklisted_domains))) {
            Response::json(language()->link->error_message->blacklisted_domain, 'error');
        }

        /* Check the url with phishtank to make sure its not a phishing site */
        if(settings()->links->phishtank_is_enabled) {
            if(phishtank_check($url, settings()->links->phishtank_api_key)) {
                Response::json(language()->link->error_message->blacklisted_location_url, 'error');
            }
        }

        /* Check the url with google safe browsing to make sure it is a safe website */
        if(settings()->links->google_safe_browsing_is_enabled) {
            if(google_safe_browsing_check($url, settings()->links->google_safe_browsing_api_key)) {
                Response::json(language()->link->error_message->blacklisted_location_url, 'error');
            }
        }
    }

}
