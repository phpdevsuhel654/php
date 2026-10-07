<?php
/** @var array $config */
?>
<nav class="main-nav">
    <div class="nav-inner">
        <a href="<?= htmlspecialchars($config['app']['url'], ENT_QUOTES, 'UTF-8') ?>/stores.php">Stores</a>
        <a href="<?= htmlspecialchars($config['app']['url'], ENT_QUOTES, 'UTF-8') ?>/install.php">Install Store</a>
    </div>
</nav>
