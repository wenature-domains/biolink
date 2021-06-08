<?php defined('ALTUMCODE') || die() ?>

<div data-biolink-block-id="<?= $data->link->biolink_block_id ?>" class="col-12 my-2">
    <a href="#" data-toggle="modal" data-target="#mail_<?= $data->link->link_id ?>" class="btn btn-block btn-primary link-btn <?= $data->link->design->link_class ?>" style="<?= $data->link->design->link_style ?>">
        <div class="link-btn-image-wrapper <?= $data->link->design->border_class ?>" <?= $data->link->settings->image ? null : 'style="display: none;"' ?>>
            <img src="<?= $data->link->settings->image ? (mb_substr($data->link->settings->image, 0, 4) === "http" ? $data->link->settings->image : SITE_URL . UPLOADS_URL_PATH . 'block_thumbnail_images/' . $data->link->settings->image) : null ?>" class="link-btn-image" loading="lazy" />
        </div>

        <?php if($data->link->settings->icon): ?>
            <i class="<?= $data->link->settings->icon ?> mr-1"></i>
        <?php endif ?>

        <?= $data->link->settings->name ?>
    </a>

</div>

<?php ob_start() ?>
<div class="modal fade" id="mail_<?= $data->link->link_id ?>" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title"><?= $data->link->settings->name ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= language()->global->close ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="mail_form_<?= $data->link->biolink_block_id ?>" method="post" role="form">
                    <input type="hidden" name="token" value="<?= \Altum\Middlewares\Csrf::get() ?>" required="required" />
                    <input type="hidden" name="biolink_block_id" value="<?= $data->link->biolink_block_id ?>" />

                    <div class="notification-container"></div>

                    <div class="form-group">
                        <input type="email" class="form-control form-control-lg" name="email" required="required" placeholder="<?= $data->link->settings->email_placeholder ?>" />
                    </div>

                    <div class="form-group">
                        <input type="text" class="form-control form-control-lg" name="name" required="required" placeholder="<?= $data->link->settings->name_placeholder ?>" />
                    </div>

                    <?php if($data->link->settings->show_agreement): ?>
                    <div class="d-flex align-items-center">
                        <input type="checkbox" id="agreement" name="agreement" class="mr-3" required="required" />
                        <label for="agreement" class="text-muted mb-0">
                            <a href="<?= $data->link->settings->agreement_url ?>">
                                <?= $data->link->settings->agreement_text ?>
                            </a>
                        </label>
                    </div>
                    <?php endif ?>

                    <div class="text-center mt-4">
                        <button type="submit" name="submit" class="btn btn-block btn-primary"><?= $data->link->settings->button_text ?></button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<?php \Altum\Event::add_content(ob_get_clean(), 'modals') ?>

