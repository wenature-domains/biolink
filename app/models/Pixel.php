<?php

namespace Altum\Models;

class Pixel extends Model {

    public function get_pixels($user_id) {

        $result = database()->query("SELECT * FROM `pixels` WHERE `user_id` = {$user_id}");
        $data = [];

        while($row = $result->fetch_object()) {
            $data[$row->pixel_id] = $row;
        }

        return $data;
    }

    public function get_pixels_by_pixels_ids($pixels_ids) {

        if(empty($pixels_ids)) return [];

        $pixels_ids_plain = implode(',', $pixels_ids);

        $result = database()->query("SELECT * FROM `pixels` WHERE `pixel_id` IN({$pixels_ids_plain})");
        $data = [];

        while($row = $result->fetch_object()) {
            $data[$row->pixel_id] = $row;
        }

        return $data;
    }
}
