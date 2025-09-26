<?php
// templates/dashboard.php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><span class="dashicons dashicons-money-alt"></span> Money Transfer Portal</h1>
    
    <?php 
    $user = wp_get_current_user();
    $roles = $user->roles;
    $role_names = array_map('ucfirst', $roles);
    ?>
    <div class="mtp-user-role-info">
        <strong>Welcome, <?php echo esc_html($user->display_name); ?></strong> 
        <span style="margin-left: 10px;">Role: <?php echo implode(', ', $role_names); ?></span>
        <?php if (in_array('editor', $roles)): ?>
            <span style="margin-left: 10px; color: #856404;">⚠️ Limited access - Contact administrator for full permissions</span>
        <?php endif; ?>
    </div>
    
    <!-- Statistics -->
    <div class="mtp-dashboard-stats">
        <div class="mtp-stat-box">
            <h3><?php echo number_format($total_parties); ?></h3>
            <p>Active Parties</p>
        </div>
        
        <div class="mtp-stat-box">
            <h3><?php echo mtp_format_currency($total_balance ?: 0); ?></h3>
            <p>Total Balance</p>
        </div>
        
        <div class="mtp-stat-box">
            <h3><?php echo number_format($today_transactions); ?></h3>
            <p>Today's Transactions</p>
        </div>
        
        <div class="mtp-stat-box">
            <h3><?php echo mtp_format_currency($today_amount ?: 0); ?></h3>
            <p>Today's Amount</p>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <div class="mtp-table-container">
        <div class="mtp-table-header">
            <h3><span class="dashicons dashicons-list-view"></span> Recent Transactions</h3>
        </div>
        
        <?php if (!empty($recent_transactions)): ?>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Party</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_transactions as $transaction): ?>
                    <tr>
                        <td><strong><?php echo esc_html($transaction->reference_number); ?></strong></td>
                        <td><?php echo esc_html($transaction->party_name); ?></td>
                        <td>
                            <span class="mtp-badge mtp-badge-<?php echo $transaction->transaction_type; ?>">
                                <?php echo ucfirst($transaction->transaction_type); ?>
                            </span>
                        </td>
                        <td><?php echo mtp_format_currency($transaction->amount); ?></td>
                        <td><?php echo date('M j, Y H:i', strtotime($transaction->transaction_date)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="mtp-empty-state">
                <div class="mtp-empty-icon">
                    <span class="dashicons dashicons-money"></span>
                </div>
                <h3>No Transactions Yet</h3>
                <p>Start by adding parties and creating transactions.</p>
                <a href="?page=mtp-parties" class="button button-primary">Add First Party</a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions -->
    <div class="mtp-actions">
        <a href="?page=mtp-parties" class="button button-primary">
            <span class="dashicons dashicons-plus"></span> Add Party
        </a>
        <a href="?page=mtp-transactions" class="button button-secondary">
            <span class="dashicons dashicons-plus"></span> Add Transaction
        </a>
        <a href="?page=mtp-reports" class="button button-secondary">
            <span class="dashicons dashicons-chart-bar"></span> View Reports
        </a>
    </div>
</div>