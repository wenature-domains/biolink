<?php defined('ALTUMCODE') || die() ?>

<div class="index-container">
    <div class="container">
        <?= \Altum\Alerts::output_alerts() ?>

        <div class="row">
            <div class="col">
                <div class="text-left">
                    <h1 class="index-header mb-4 grass-text-white">Your <br/>Ecological Domain</h1>
                    <p class="index-subheader mb-5 grass-text-white">deIdentity // deLinks // deJournal // deChannel // deWallet // deFinance // deFootprint // deApps</p>

                    <div>
                        <a href="<?= url('register') ?>" class="btn btn-primary index-button"><?= language()->index->sign_up ?></a>
                    </div>
                </div>
            </div>

            <div class="d-none d-lg-block col">
                <img src="<?= SITE_URL . ASSETS_URL_PATH . 'images/S1.1.png' ?>" class="index-image" />
            </div>
        </div>
    </div>
</div>

<!-- <div class="container mt-10">
    <div class="row">
        <div class="col-md-6">
            <img src="<?= url(THEME_URL_PATH . 'assets/images/presentation-1.png') ?>" class="img-fluid shadow" loading="lazy" />
        </div>

        <div class="col-md-6 d-flex align-items-center">
            <div>
                <span class="fa-stack fa-2x">
                  <i class="fa fa-circle fa-stack-2x text-primary-100"></i>
                  <i class="fa fa-globe fa-stack-1x text-primary"></i>
                </span>

                <h2 class="mt-3"><?= language()->index->presentation1->header ?></h2>

                <p class="mt-3"><?= language()->index->presentation1->subheader ?></p>
            </div>
        </div>
    </div>
</div>

<div class="container mt-10">
    <div class="row">
        <div class="col-md-6 d-flex align-items-center">
            <div>
                <span class="fa-stack fa-2x">
                  <i class="fa fa-circle fa-stack-2x text-primary-100"></i>
                  <i class="fa fa-users fa-stack-1x text-primary"></i>
                </span>

                <h2 class="mt-3"><?= language()->index->presentation2->header ?></h2>

                <p class="mt-3"><?= language()->index->presentation2->subheader ?></p>
            </div>
        </div>

        <div class="col-md-6">
            <img src="<?= url(THEME_URL_PATH . 'assets/images/presentation-2.png') ?>" class="img-fluid shadow" loading="lazy" />
        </div>
    </div>
</div>

<div class="container mt-10">
    <div class="row">
        <div class="col-md-6">
            <img src="<?= url(THEME_URL_PATH . 'assets/images/presentation-3.png') ?>" class="img-fluid shadow" loading="lazy" />
        </div>

        <div class="col-md-6 d-flex align-items-center">
            <div>
                <span class="fa-stack fa-2x">
                  <i class="fa fa-circle fa-stack-2x text-primary-100"></i>
                  <i class="fa fa-link fa-stack-1x text-primary"></i>
                </span>

                <h2 class="mt-3"><?= language()->index->presentation3->header ?></h2>

                <p class="mt-3"><?= language()->index->presentation3->subheader ?></p>
            </div>
        </div>
    </div>
</div>

<div class="container mt-10">
    <div class="row">
        <div class="col-md-6 d-flex align-items-center">
            <div>
                <span class="fa-stack fa-2x">
                  <i class="fa fa-circle fa-stack-2x text-primary-100"></i>
                  <i class="fa fa-chart-line fa-stack-1x text-primary"></i>
                </span>

                <h2 class="mt-3"><?= language()->index->presentation4->header ?></h2>

                <p class="mt-3"><?= language()->index->presentation4->subheader ?></p>
            </div>
        </div>

        <div class="col-md-6">
            <img src="<?= url(THEME_URL_PATH . 'assets/images/presentation-4.png') ?>" class="img-fluid shadow" loading="lazy" />
        </div>
    </div>
</div>

<div class="container mt-10">
    <div class="text-center mb-8">
        <h2><?= language()->index->pricing->header ?></h2>

        <p class="text-muted"><?= language()->index->pricing->subheader ?></p>
    </div>

    <?= $this->views['plans'] ?>
</div> -->
