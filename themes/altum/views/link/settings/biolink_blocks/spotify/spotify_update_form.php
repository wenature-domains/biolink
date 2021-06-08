<?php defined('ALTUMCODE') || die() ?>

<form name="update_biolink_" method="post" role="form">
    <input type="hidden" name="token" value="<?= \Altum\Middlewares\Csrf::get() ?>" required="required" />
    <input type="hidden" name="request_type" value="update" />
    <input type="hidden" name="type" value="spotify" />
    <input type="hidden" name="biolink_block_id" value="<?= $row->biolink_block_id ?>" />

    <div class="notification-container"></div>

    <div class="form-group">
        <label><i class="fa fa-fw fa-signature fa-sm mr-1"></i> <?= language()->create_biolink_spotify_modal->input->location_url ?></label>
        <input type="text" class="form-control" name="location_url" value="<?= $row->location_url ?>" placeholder="<?= language()->create_biolink_spotify_modal->input->location_url_placeholder ?>" required="required" />
    </div>

    <div class="mt-4">
        <button type="submit" name="submit" class="btn btn-block btn-primary"><?= language()->global->update ?></button>
    </div>
</form>
