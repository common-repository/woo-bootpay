<div class="inline error"><?= $errorMsg ?></div>
<?php if( $console ) : ?>
    <script>console.error('<?= $errorMsg ?>');</script>
<?php endif; ?>