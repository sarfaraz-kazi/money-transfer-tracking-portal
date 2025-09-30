<?php
// templates/reports.php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><span class="dashicons dashicons-chart-bar"></span> Reports</h1>
    
    <!-- Report Controls -->
    <div class="mtp-report-controls">
        <form method="GET" class="mtp-report-form">
            <input type="hidden" name="page" value="mtp-reports">
            
            <div class="form-group">
                <label for="date_from">From Date:</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($date_from); ?>">
            </div>
            
            <div class="form-group">
                <label for="date_to">To Date:</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($date_to); ?>">
            </div>
            
            <div class="form-group">
                <button type="button" id="generate-report-btn" class="button button-primary">
                    <span class="dashicons dashicons-chart-line"></span> Generate Report
                </button>
            </div>
        </form>
        
        <!-- Quick Date Buttons -->
        <div style="margin-top: 15px;">
            <strong>Quick Select:</strong>
            <button type="button" class="button button-small quick-date-btn" data-period="today">Today</button>
            <button type="button" class="button button-small quick-date-btn" data-period="week">This Week</button>
            <button type="button" class="button button-small quick-date-btn" data-period="month">This Month</button>
        </div>
    </div>
    
    <?php if ($report_data): ?>
    <!-- Report Content -->
    <div class="mtp-report-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>Report for <?php echo date('M j, Y', strtotime($date_from)); ?> to <?php echo date('M j, Y', strtotime($date_to)); ?></h2>
            <div>
                <button type="button" id="export-report-btn" class="button">
                    <span class="dashicons dashicons-download"></span> Export CSV
                </button>
                <button type="button" id="print-report-btn" class="button">
                    <span class="dashicons dashicons-printer"></span> Print
                </button>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="mtp-report-summary">
            <div class="mtp-summary-card">
                <div class="mtp-summary-value"><?php echo number_format($report_data->total_transactions); ?></div>
                <div class="mtp-summary-label">Total Transactions</div>
            </div>
            
            <div class="mtp-summary-card">
                <div class="mtp-summary-value"><?php echo mtp_format_currency($report_data->total_sales); ?></div>
                <div class="mtp-summary-label">Total Sales</div>
            </div>
            
            <div class="mtp-summary-card">
                <div class="mtp-summary-value"><?php echo mtp_format_currency($report_data->total_received); ?></div>
                <div class="mtp-summary-label">Total Received</div>
            </div>
            
            <div class="mtp-summary-card">
                <div class="mtp-summary-value"><?php echo number_format($report_data->active_parties); ?></div>
                <div class="mtp-summary-label">Active Parties</div>
            </div>
        </div>
        
        <!-- Party-wise Report -->
        <h3>Party-wise Summary</h3>
        <table class="wp-list-table widefat striped" border="1">
            <thead>
                <tr>
                    <th>Party ID</th>
                    <th>Party Name</th>
                    <th>Current Balance</th>
                    <th>Period Sales</th>
                    <th>Period Received</th>
                    <th>Transactions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_period_sales = 0;
                $total_period_received = 0;
                $total_transactions_count = 0;
                
                foreach ($report_data->party_data as $party): 
                    $total_period_sales += $party->period_sales;
                    $total_period_received += $party->period_received;
                    $total_transactions_count += $party->transaction_count;
                ?>
                <tr class="party-report-row">
                    <td><strong class="party-name"><?php echo esc_html($party->id); ?></strong></td>
                    <td><strong class="party-name"><?php echo esc_html($party->party_name); ?></strong></td>
                    <td class="party-balance <?php echo $party->current_balance < 0 ? 'mtp-negative' : ($party->current_balance > 0 ? 'mtp-positive' : 'mtp-zero'); ?>">
                        <?php echo mtp_format_currency($party->current_balance); ?>
                    </td>
                    <td class="party-sales"><?php echo mtp_format_currency($party->period_sales); ?></td>
                    <td class="party-received"><?php echo mtp_format_currency($party->period_received); ?></td>
                    <td class="party-count"><?php echo number_format($party->transaction_count); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f0f0f0; font-weight: bold;">
                    <th>TOTALS:</th>
                    <th></th>
                    <th><?php echo mtp_format_currency($total_period_sales); ?></th>
                    <th><?php echo mtp_format_currency($total_period_received); ?></th>
                    <th><?php echo number_format($total_transactions_count); ?></th>
                </tr>
            </tfoot>
        </table>
        
        <!-- Net Position -->
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px;">
            <h3>Net Position</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div>
                    <strong>Period Sales:</strong><br>
                    <span style="color: #dc3545; font-size: 18px;"><?php echo mtp_format_currency($report_data->total_sales); ?></span>
                </div>
                <div>
                    <strong>Period Received:</strong><br>
                    <span style="color: #198754; font-size: 18px;"><?php echo mtp_format_currency($report_data->total_received); ?></span>
                </div>
                <div>
                    <strong>Net Amount:</strong><br>
                    <?php 
                    $net_amount = $report_data->total_received - $report_data->total_sales;
                    $net_class = $net_amount >= 0 ? '#198754' : '#dc3545';
                    ?>
                    <span style="color: <?php echo $net_class; ?>; font-size: 18px; font-weight: bold;">
                        <?php echo mtp_format_currency($net_amount); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    
    <!-- Empty State -->
    <div class="mtp-empty-state">
        <div class="mtp-empty-icon">
            <span class="dashicons dashicons-chart-pie"></span>
        </div>
        <h3>Generate Your Report</h3>
        <p>Select date range above and click "Generate Report" to view your business data.</p>
    </div>
    
    <?php endif; ?>
</div>

<style>
/* Print styles */
@media print {
    .mtp-actions,
    .mtp-report-controls,
    .button,
    .dashicons {
        display: none !important;
    }
    
    .mtp-report-content {
        border: none;
        box-shadow: none;
    }
    
    .wp-list-table {
        border-collapse: collapse;
    }
    
    .wp-list-table th,
    .wp-list-table td {
        border: 1px solid #000;
        padding: 8px;
    }
    
    .mtp-positive {
        color: #000 !important;
    }
    
    .mtp-negative {
        color: #000 !important;
    }
}
</style>