<?php
$config = json_decode(file_get_contents(ABSPATH . '../../etc/config.json'), true);
$donated_amount = $config['donated_amount'] ?? 0;

if ($donated_amount < 7.0) {
    add_action('wp_footer', function () {
        echo '<p style="text-align:center;padding:8px 0;margin:0;font-size:13px;color:#666;">Powered by <a href="https://no-cost.site" target="_blank" rel="noopener" style="color:#4D698E;">no-cost.site</a></p>';
    });
}
