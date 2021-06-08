<?php defined('ALTUMCODE') || die() ?>

<?php require THEME_PATH . 'views/partials/ads_header.php' ?>

<div class="container">
    <div class="d-flex flex-column align-items-center">
        <div class="col-xs-12 col-sm-10 col-md-8 col-lg-5">
            <?= \Altum\Alerts::output_alerts() ?>

            <div class="card border-0">
                <div class="card-body p-5">
                    <h4 class="card-title"><?= language()->register->header ?></h4>

                    <form action="" method="post" class="mt-4" role="form">
                        <div class="form-group">
                            <label for="name"><?= language()->register->form->name ?></label>
                            <input id="name" type="text" name="name" class="form-control <?= \Altum\Alerts::has_field_errors('name') ? 'is-invalid' : null ?>" value="<?= $data->values['name'] ?>" placeholder="<?= language()->register->form->name_placeholder ?>" required="required" autofocus="autofocus" />
                            <?= \Altum\Alerts::output_field_error('name') ?>
                        </div>

                        <div class="form-group">
                            <label for="email"><?= language()->register->form->email ?></label>
                            <input id="email" type="text" name="email" class="form-control <?= \Altum\Alerts::has_field_errors('email') ? 'is-invalid' : null ?>" value="<?= $data->values['email'] ?>" placeholder="<?= language()->register->form->email_placeholder ?>" required="required" />
                            <?= \Altum\Alerts::output_field_error('email') ?>
                        </div>

                        <div class="form-group">
                            <label for="password"><?= language()->register->form->password ?></label>
                            <input id="password" type="password" name="password" class="form-control <?= \Altum\Alerts::has_field_errors('password') ? 'is-invalid' : null ?>" value="<?= $data->values['password'] ?>" placeholder="<?= language()->register->form->password_placeholder ?>" required="required" />
                            <?= \Altum\Alerts::output_field_error('password') ?>
                        </div>

                        <?php if(settings()->captcha->register_is_enabled): ?>
                        <div class="form-group">
                            <?php $data->captcha->display() ?>
                        </div>
                        <?php endif ?>

                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="accept" class="custom-control-input" id="accept" required="required">
                            <label class="custom-control-label" for="accept">
                                <small class="text-muted">
                                    <?= sprintf(
                                        language()->register->form->accept,
                                        '<a>' . language()->global->terms_and_conditions . '</a>',
                                        '<a>' . language()->global->privacy_policy . '</a>'
                                    ) ?>
                                </small>
                            </label>
                        </div>

                        <div class="form-group mt-3">
                            <button type="submit" name="submit" class="btn btn-primary btn-block"><?= language()->register->form->register ?></button>
                        </div>

                        <div class="row">
                            <?php if(settings()->facebook->is_enabled): ?>
                                <div class="col-sm mt-1">
                                    <a href="<?= $data->facebook_login_url ?>" class="btn btn-light btn-block"><?= sprintf(language()->login->display->facebook, "<i class=\"fab fa-fw fa-facebook\"></i>") ?></a>
                                </div>
                            <?php endif ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
<!-- 
        <div class="text-center mt-4">
            <small><a href="login" class="grass-text-white" role="button"><?= language()->register->login ?></a></small>
        </div> -->
    </div>
</div>



