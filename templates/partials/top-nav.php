<?php
// templates/partials/top-nav.php
if (!defined('ABSPATH')) exit;

$current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
$user = wp_get_current_user();
$display_name = $user && $user->exists() ? $user->display_name : 'admin';

// Map slugs to labels and URLs
$nav_items = array(
    'money-transfer-portal' => array('label' => 'Parties', 'url' => admin_url('admin.php?page=money-transfer-portal')),
    'mtp-transactions' => array('label' => 'Transactions', 'url' => admin_url('admin.php?page=mtp-transactions')),
    'mtp-reports' => array('label' => 'Reports', 'url' => admin_url('admin.php?page=mtp-reports')),
);

?>
<div class="mtp-topbar">
    <div class="mtp-topbar__brand">
        <span class="dashicons dashicons-money-alt"></span>
        <span class="mtp-topbar__title">Transaction Book</span>
    </div>
    <nav class="mtp-topbar__nav">
        <?php foreach ($nav_items as $slug => $item): ?>
            <a class="mtp-topbar__link <?php echo $current_page === $slug ? 'is-active' : ''; ?>" href="<?php echo esc_url($item['url']); ?>">
                <?php echo esc_html($item['label']); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="mtp-topbar__user">Howdy, <?php echo esc_html($display_name); ?></div>
</div>