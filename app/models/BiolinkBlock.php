<?php

namespace Altum\Models;

class BiolinkBlock extends Model {

    public function delete($biolink_block_id) {

        if(!$biolink_block = db()->where('biolink_block_id', $biolink_block_id)->getOne('biolinks_blocks')) {
            die();
        }

        /* Delete the stored files of the link, if any */
        if(in_array($biolink_block->type, ['image', 'image_grid'])) {
            $biolink_block->settings = json_decode($biolink_block->settings);

            /* Delete block image */
            if(!empty($biolink_block->settings->image) && file_exists(UPLOADS_PATH . 'block_images/' . $biolink_block->settings->image)) {
                unlink(UPLOADS_PATH . 'block_images/' . $biolink_block->settings->image);
            }
        }

        /* Delete the stored files of the link, if any */
        if(in_array($biolink_block->type, ['link', 'mail', 'vcard'])) {
            $biolink_block->settings = json_decode($biolink_block->settings);

            /* Delete thumbnail image */
            if(!empty($biolink_block->settings->image) && file_exists(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image)) {
                unlink(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image);
            }
        }

        /* Delete from database */
        db()->where('biolink_block_id', $biolink_block_id)->delete('biolinks_blocks');

        /* Clear the cache */
        \Altum\Cache::$adapter->deleteItemsByTag('biolinks_links_user_' . $biolink_block->user_id);
        \Altum\Cache::$adapter->deleteItemsByTag('link_id=' . $biolink_block->link_id);

    }
}
