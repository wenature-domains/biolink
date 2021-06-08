<?php defined('ALTUMCODE') || die() ?>

<body class="link-body <?= $data->link->design->background_class ?>" style="<?= $data->link->design->background_style ?>">
    <div class="container animate__animated animate__fadeIn">
        <div class="row d-flex justify-content-center text-center">
            <div class="col-md-6 link-content <?= isset($_GET['preview']) ? 'container-disabled-simple' : null ?>">

                <?php require THEME_PATH . 'views/partials/ads_header_biolink.php' ?>

                <header class="d-flex flex-column align-items-center" style="<?= $data->link->design->text_style ?>">
                    <img id="image" src="<?= $data->link->settings->image ? SITE_URL . UPLOADS_URL_PATH . 'avatars/' . $data->link->settings->image : null ?>" alt="<?= language()->link->biolink->image_alt ?>" class="link-image" <?= !empty($data->link->settings->image) && file_exists(UPLOADS_PATH . 'avatars/' . $data->link->settings->image) ? null : 'style="display: none;"' ?> />

                    <div class="d-flex flex-row align-items-center mt-4">
                        <h1 id="title"><?= $data->link->settings->title ?></h1>

                        <?php if($data->user->plan_settings->verified && $data->link->settings->display_verified): ?>
                        <span data-toggle="tooltip" title="<?= language()->global->verified ?>" class="link-verified ml-1"><i class="fa fa-fw fa-check-circle fa-1x"></i></span>
                        <?php endif ?>
                    </div>

                    <p id="description"><?= $data->link->settings->description ?></p>
                </header>

                <main id="links" class="mt-4">
                    <div class="row">
                        <?php if($data->biolink_blocks): ?>
                            <?php foreach($data->biolink_blocks as $row): ?>

                                <?php

                                /* Check if its a scheduled link and we should show it or not */
                                if(
                                    !empty($row->start_date) &&
                                    !empty($row->end_date) &&
                                    (
                                        \Altum\Date::get('', null) < \Altum\Date::get($row->start_date, null, \Altum\Date::$default_timezone) ||
                                        \Altum\Date::get('', null) > \Altum\Date::get($row->end_date, null, \Altum\Date::$default_timezone)
                                    )
                                ) {
                                    continue;
                                }

                                /* Check if the user has permissions to use the link */
                                if(!$data->user->plan_settings->enabled_biolink_blocks->{$row->type}) {
                                    continue;
                                }

                                $row->utm = $data->link->settings->utm;

                                ?>

                                <?= \Altum\Link::get_biolink_link($row, $data->user) ?? null ?>

                            <?php endforeach ?>
                        <?php endif ?>
                    </div>

                    <?php if($data->user->plan_settings->socials): ?>
                    <div id="socials" class="d-flex flex-wrap justify-content-center mt-5">

                    <?php $biolink_socials = require APP_PATH . 'includes/biolink_socials.php'; ?>
                    <?php foreach($data->link->settings->socials as $key => $value): ?>
                        <?php if($value): ?>

                        <div class="mx-3 mb-3">
                            <span >
                                <a href="<?= sprintf($biolink_socials[$key]['format'], $value) ?>" target="_blank">
                                    <i
                                        data-toggle="tooltip"
                                        title="<?= language()->link->settings->socials->{$key}->name ?>"
                                        class="<?= language()->link->settings->socials->{$key}->icon ?> fa-fw fa-2x"
                                        style="<?= $data->link->design->socials_style ?>">
                                    </i>
                                </a>
                            </span>
                        </div>

                        <?php endif ?>
                    <?php endforeach ?>

                    </div>
                    <?php endif ?>

                </main>

                <?php require THEME_PATH . 'views/partials/ads_footer_biolink.php' ?>

                <footer class="link-footer">
                    <?php if($data->link->settings->display_branding): ?>
                        <?php if(isset($data->link->settings->branding, $data->link->settings->branding->name, $data->link->settings->branding->url) && !empty($data->link->settings->branding->name)): ?>
                            <a id="branding" href="<?= !empty($data->link->settings->branding->url) ? $data->link->settings->branding->url : '#' ?>" style="<?= $data->link->design->text_style ?>"><?= $data->link->settings->branding->name ?></a>
                        <?php else: ?>
                            <a id="branding" href="<?= url() ?>" style="<?= $data->link->design->text_style ?>"><?= settings()->links->branding ?></a>
                        <?php endif ?>
                    <?php endif ?>
                </footer>

            </div>
        </div>
    </div>

    <?= \Altum\Event::get_content('modals') ?>
</body>

<?php ob_start() ?>
<script>
    let base_url = <?= json_encode(SITE_URL) ?>;

    /* Internal tracking for biolink links */
    $('a[data-biolink-block-id]').on('click', event => {
        let biolink_block_id = $(event.currentTarget).data('biolink-block-id');

        $.ajax(`${base_url}l/link?biolink_block_id=${biolink_block_id}&no_redirect`);
    });

    /* Go over all mail buttons to make sure the user can still submit mail */
    $('form[id^="mail_"]').each((index, element) => {
        let biolink_block_id = $(element).find('input[name="biolink_block_id"]').val();
        let is_converted = localStorage.getItem(`mail_${biolink_block_id}`);

        if(is_converted) {
            /* Set the submit button to disabled */
            $(element).find('button[type="submit"]').attr('disabled', 'disabled');
        }
    });
        /* Form handling for mail submissions if any */
    $('form[id^="mail_"]').on('submit', event => {
        let biolink_block_id = $(event.currentTarget).find('input[name="biolink_block_id"]').val();
        let is_converted = localStorage.getItem(`mail_${biolink_block_id}`);

        if(!is_converted) {

            $.ajax({
                type: 'POST',
                url: `${base_url}l/link/mail`,
                data: $(event.currentTarget).serialize(),
                success: (data) => {
                    let notification_container = $(event.currentTarget).find('.notification-container');

                    if (data.status == 'error') {
                        notification_container.html('');

                        display_notifications(data.message, 'error', notification_container);
                    } else if (data.status == 'success') {

                        display_notifications(data.message, 'success', notification_container);

                        setTimeout(() => {

                            /* Hide modal */
                            $(event.currentTarget).closest('.modal').modal('hide');

                            /* Remove the notification */
                            notification_container.html('');

                            /* Set the localstorage to mention that the user was converted */
                            localStorage.setItem(`mail_${biolink_block_id}`, true);

                            /* Set the submit button to disabled */
                            $(event.currentTarget).find('button[type="submit"]').attr('disabled', 'disabled');

                        }, 1000);

                    }
                },
                dataType: 'json'
            });

        }

        event.preventDefault();
    })
</script>

<?= $this->views['pixels'] ?? null ?>

<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

