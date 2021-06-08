<?php

namespace Altum\Models;

class Link extends Model {

    public function delete($link_id) {

        if(!$link = db()->where('link_id', $link_id)->getOne('links', ['user_id', 'link_id', 'type', 'settings'])) {
            return;
        }

        /* Process to delete the stored files of the link */
        if($link->type == 'biolink') {
            $link->settings = json_decode($link->settings);

            /* Delete avatar */
            if(!empty($link->settings->image) && file_exists(UPLOADS_PATH . 'avatars/' . $link->settings->image)) {
                unlink(UPLOADS_PATH . 'avatars/' . $link->settings->image);
            }

            /* Delete background */
            if(is_string($link->settings->background) && !empty($link->settings->background) && file_exists(UPLOADS_PATH . 'backgrounds/' . $link->settings->background)) {
                unlink(UPLOADS_PATH . 'backgrounds/' . $link->settings->background);
            }

            /* Delete seo opengraph image */
            if(is_string($link->settings->seo->image) && !empty($link->settings->seo->image) && file_exists(UPLOADS_PATH . 'backgrounds/' . $link->settings->seo->image)) {
                unlink(UPLOADS_PATH . 'block_images/' . $link->settings->seo->image);
            }

            /* Get all the available biolink blocks and iterate over them to delete the stored images */
            $result = database()->query("SELECT `biolink_block_id` FROM `biolinks_blocks` WHERE `link_id` = {$link->link_id}");
            while($row = $result->fetch_object()) {

                (new \Altum\Models\BiolinkBlock())->delete($row->biolink_block_id);

            }
        }

        /* Delete from database */
        db()->where('link_id', $link_id)->delete('links');

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $link->user_id);
        \Altum\Cache::$adapter->deleteItemsByTag('link_id=' . $link->link_id);

    }
}
