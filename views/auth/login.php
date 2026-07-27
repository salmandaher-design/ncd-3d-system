<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo">
            <span class="brand-mark"><i class="bi bi-printer-fill"></i></span>
            <h1><?= e(APP_NAME) ?></h1>
            <p><?= e(APP_FULL_NAME) ?> · <?= e(APP_TAGLINE) ?></p>
        </div>

        <?= Flash::render() ?>

        <form method="post" action="<?= url('auth/login') ?>" autocomplete="off">
            <?= Csrf::field() ?>
            <div class="field">
                <label for="email">Email address</label>
                <input class="input" type="email" id="email" name="email" value="<?= old('email') ?>"
                       placeholder="you@ncd.sy" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input class="input" type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn2 primary block">
                <i class="bi bi-box-arrow-in-right"></i> Sign in
            </button>
        </form>

        <div class="auth-demo">
            <i class="bi bi-info-circle"></i>
            Accounts are created by the administrator. Contact them if you need access.
        </div>
    </div>
</div>
<?php unset($_SESSION['_old']); ?>
