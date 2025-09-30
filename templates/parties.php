<?php
// templates/parties.php
if (!defined('ABSPATH')) exit;

$today = date('M j, Y');
$total_previous = 0;
$total_send = $up_total_send =0;
$total_receive = $up_total_receive = 0;
$total_current = 0;
// Calculate total balances
$total_current_balance = 0;
if (!empty($parties)) {
    foreach ($parties as $party) {
        $total_current_balance += $party->current_balance;
        $up_total_send += $party->today_send;
        $up_total_receive += $party->today_receive;
    }
}
?>

<div class="wrap mttp">
   
    
    <?php 
    $user = wp_get_current_user();
    $roles = $user->roles;
    $role_names = array_map('ucfirst', $roles);
    $display_name= ucfirst($user->display_name);
    ?>
     <div class="top-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1><span class="dashicons dashicons-groups"></span> Daily Transaction Book - <?php echo $today; ?></h1>
         <button type="button" class="button button-primary button-large" id="add-party-btn">
             <span class="dashicons dashicons-plus"></span> Add New Party
         </button>
         <input type="text" id="party-search" placeholder="Search parties..." style="width: 300px; margin-left: 20px;height: 50px">

         <div style="float: right; color: #666; font-size: 14px;">
             <strong>Today:</strong> <span style="font-weight: 700"><?php echo $today; ?></span> |
             <strong>Total Parties:</strong> <span style="font-weight: 700;color: #198754"><?php echo count($parties); ?></span>
         </div>
        <div class="mtp-total-balance">
            <strong >Total Current Balance: <span style="color: #<?php echo ($total_current_balance > 0) ? '198754':'dc3545' ?>;font-weight: 700"><?php echo mtp_format_currency($total_current_balance); ?></span></strong>
        </div>
    </div>

    <!-- Daily Summary -->
    <div style="margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px;">
        <h3 style="margin-top: 0;">Today's Summary</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                <div style="font-size: 24px; font-weight: bold; color: #dc3545;"><?php echo mtp_format_currency($up_total_send); ?></div>
                <div style="font-size: 14px; color: #666;">Total Send Today</div>
            </div>
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                <div style="font-size: 24px; font-weight: bold; color: #198754;"><?php echo mtp_format_currency($up_total_receive); ?></div>
                <div style="font-size: 14px; color: #666;">Total Receive Today</div>
            </div>
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                <div style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo mtp_format_currency($up_total_receive - $up_total_send); ?></div>
                <div style="font-size: 14px; color: #666;">Net Amount Today</div>
            </div>
        </div>
    </div>
    <!-- Parties Table -->
    <div class="mtp-table-container">
        <div class="mtp-table-header">
            <h3>Today's Transactions</h3>
        </div>
        
        <?php if (!empty($parties)): ?>
            <table class="wp-list-table widefat striped parties-table" border="1">
                <thead>
                    <tr>
                        <th style="width: 50px;">Party ID</th>
                        <th style="width: 180px;">Party Name</th>
                        <th style="width: 100px;">Previous Balance</th>
                        <th style="width: 100px;">Today Send</th>
                        <th style="width: 100px;">Today Receive</th>
                        <th style="width: 120px;">Current Balance</th>
                        <th style="width: 100px;">Contact</th>
                        <th style="width: 160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                   
                    
                    foreach ($parties as $party): 
                        $total_previous += $party->previous_balance;
                        $total_send += $party->today_send;
                        $total_receive += $party->today_receive;
                        $total_current += $party->current_balance;
                    ?>
                    <tr data-party-id="<?php echo $party->id; ?>">
                        <td><?php echo $party->id; ?></td>
                        <td>
                            <strong class="party-name"><?php echo esc_html($party->party_name); ?></strong>
                            <span class="party-email" style="display: none;"><?php echo esc_html($party->email); ?></span>
                            <span class="party-contact" style="display: none;"><?php echo esc_html($party->contact_number); ?></span>
                            <span class="party-address" style="display: none;"><?php echo esc_html($party->address); ?></span>
                        </td>
                        <td class="previous-balance" style="color: #<?php echo ($party->previous_balance > 0) ? '198754':'dc3545' ?>;font-weight: 700"><?php echo mtp_format_currency($party->previous_balance); ?></td>
                        <td class="today-send" style="color: #dc3545; font-weight: 600;">
                            <?php echo $party->today_send > 0 ? mtp_format_currency($party->today_send) : '—'; ?>
                        </td>
                        <td class="today-receive" style="color: #198754; font-weight: 600;">
                            <?php echo $party->today_receive > 0 ? mtp_format_currency($party->today_receive) : '—'; ?>
                        </td>
                        <td class="current-balance <?php echo $party->current_balance < 0 ? 'mtp-negative' : ($party->current_balance > 0 ? 'mtp-positive' : 'mtp-zero'); ?>">
                            <strong><?php echo mtp_format_currency($party->current_balance); ?></strong>
                        </td>
                        <td><?php echo esc_html($party->contact_number); ?></td>
                        <td class="mtp-table-row-actions">
                            <button type="button" class="button button-primary button-small send-btn" data-id="<?php echo $party->id; ?>" title="Send Money">
                                <span class="dashicons dashicons-arrow-up-alt"></span> Send
                            </button>
                            <button type="button" class="button button-success button-small receive-btn" data-id="<?php echo $party->id; ?>" title="Receive Money">
                                <span class="dashicons dashicons-arrow-down-alt"></span> Receive
                            </button>

                            <?php if (current_user_can('manage_options')): ?>
                            <button type="button" class="button button-small button-link-delete delete-party-btn" data-id="<?php echo $party->id; ?>" title="Delete Party">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                            <?php endif; ?>
                            <?php
                            if(isZero($party->current_balance) && $party->previous_balance !=0 && (isZero($party->today_receive) && isZero($party->today_send) )): ?>
                                <button type="button" class="button button-warning button-small migrate-btn" data-id="<?php echo $party->id; ?>" title="Update Current Balance">
                                    <span class="dashicons dashicons-update"></span>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f0f0f0; font-weight: bold; border-top: 2px solid #0073aa;">
                        <th>TOTALS:</th>
                        <th></th>
                        <th><?php echo mtp_format_currency($total_previous); ?></th>
                        <th style="color: #dc3545;"><?php echo mtp_format_currency($total_send); ?></th>
                        <th style="color: #198754;"><?php echo mtp_format_currency($total_receive); ?></th>
                        <th class="<?php echo $total_current < 0 ? 'mtp-negative' : 'mtp-positive'; ?>">
                            <?php echo mtp_format_currency($total_current); ?>
                        </th>
                        <th></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        <?php else: ?>
            <div class="mtp-empty-state">
                <div class="mtp-empty-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <h3>No Parties Found</h3>
                <p>Start by adding your first party to begin daily transactions.</p>
                <button type="button" class="button button-primary" id="add-party-btn">
                    <span class="dashicons dashicons-plus"></span> Add First Party
                </button>
            </div>
        <?php endif; ?>
    </div>
    
   
</div>

<!-- Add Party Modal -->
<div id="add-party-modal" class="mtp-modal" style="display: none;">
    <div class="mtp-modal-content">
        <div class="mtp-modal-header">
            <h2>Add New Party</h2>
            <button class="mtp-modal-close">&times;</button>
        </div>
        <form id="add-party-form">
            <div class="mtp-form-row">
                <div class="mtp-form-group">
                    <label for="party_name">Party Name *</label>
                    <input type="text" id="party_name" name="party_name" required>
                </div>
                <div class="mtp-form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="text" id="contact_number" name="contact_number">
                </div>
            </div>
            
            <div class="mtp-form-row">
                <div class="mtp-form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email">
                </div>
                <div class="mtp-form-group">
                    <label for="previous_balance">Previous Balance</label>
                    <input type="number" id="previous_balance" name="previous_balance" step="0.01" value="0">
                    <small style="color: #666;">Opening balance for today</small>
                </div>
            </div>
            
            <div class="mtp-form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3"></textarea>
            </div>
            
            <div class="mtp-form-actions">
                <button type="submit" class="button button-primary">Save Party</button>
                <button type="button" class="button mtp-modal-close">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Quick Send Modal -->
<div id="quick-send-modal" class="mtp-modal" style="display: none;">
    <div class="mtp-modal-content">
        <div class="mtp-modal-header">
            <h2>Send Money - <span id="send-party-name"></span></h2>
            <button class="mtp-modal-close">&times;</button>
        </div>
        <form id="quick-send-form">
            <input type="hidden" id="send_party_id" name="party_id">
            
            <div class="mtp-form-row">
                <div class="mtp-form-group">
                    <label for="send_amount">Amount *</label>
                    <input type="number" id="send_amount" name="amount" step="0.01" min="0" required>
                </div>
                <div class="mtp-form-group">
                    <label for="receiver_name">Receiver Name</label>
                    <input type="text" id="receiver_name" name="receiver_name" placeholder="Who will receive the money?">
                </div>
            </div>
            
            <div class="mtp-form-group">
                <label for="send_description">Description</label>
                <textarea id="send_description" name="description" rows="2" placeholder="Optional notes about this transaction"></textarea>
            </div>
            
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                <small><strong>Current Balance:</strong> <span id="send-current-balance"></span></small><br>
                <small style="color: #856404;">This amount will be deducted from the party's balance</small>
            </div>
            
            <div class="mtp-form-actions">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-arrow-up-alt"></span> Send Money
                </button>
                <button type="button" class="button mtp-modal-close">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Quick Receive Modal -->
<div id="quick-receive-modal" class="mtp-modal" style="display: none;">
    <div class="mtp-modal-content">
        <div class="mtp-modal-header">
            <h2>Receive Money - <span id="receive-party-name"></span></h2>
            <button class="mtp-modal-close">&times;</button>
        </div>
        <form id="quick-receive-form">
            <input type="hidden" id="receive_party_id" name="party_id">
            
            <div class="mtp-form-row">
                <div class="mtp-form-group">
                    <label for="receive_amount">Amount *</label>
                    <input type="number" id="receive_amount" name="amount" step="0.01" min="0" required>
                </div>
                <div class="mtp-form-group">
                    <label for="sender_name">Sender Name</label>
                    <input type="text" id="sender_name" name="sender_name" placeholder="Who is sending the money?">
                </div>
            </div>
            
            <div class="mtp-form-group">
                <label for="receive_description">Description</label>
                <textarea id="receive_description" name="description" rows="2" placeholder="Optional notes about this transaction"></textarea>
            </div>
            
            <div style="background: #d1e7dd; border: 1px solid #a3cfbb; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                <small><strong>Current Balance:</strong> <span id="receive-current-balance"></span></small><br>
                <small style="color: #0a3622;">This amount will be added to the party's balance</small>
            </div>
            
            <div class="mtp-form-actions">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-arrow-down-alt"></span> Receive Money
                </button>
                <button type="button" class="button mtp-modal-close">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.button-success {
    background: #28a745 !important;
    border-color: #28a745 !important;
    color: white !important;
}

.button-success:hover {
    background: #218838 !important;
    border-color: #218838 !important;
}

.mtp-table-row-actions .button {
    margin-right: 5px;
    font-size: 12px;
    padding: 4px 8px;
}

.mtp-table-row-actions .dashicons {
    font-size: 14px;
    vertical-align: text-top;
}

.mtp-total-balance {
    background: #999ea1;
    color: #000;
    padding: 10px 15px;
    border-radius: 4px;
    font-size: 16px;
}

/* Highlight today's active transactions */
.today-send:not(:contains('—')), 
.today-receive:not(:contains('—')) {
    background: #fff3cd;
    padding: 4px 8px;
    border-radius: 3px;
}

/* Better visual separation */
.parties-table thead tr {
    background: #f8f9fa;
}

.parties-table th {
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
    padding: 12px 8px;
}

.parties-table td {
    padding: 10px 8px;
    vertical-align: middle;
}

/* User Role Styling */
.mtp-user-role-info {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
    padding: 10px 15px;
    margin: 15px 0;
    color: #856404;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .parties-table {
        font-size: 13px;
    }
    
    .mtp-table-row-actions .button {
        font-size: 11px;
        padding: 3px 6px;
    }
    
    .mtp-total-balance {
        font-size: 14px;
        padding: 8px 12px;
    }
}

@media (max-width: 768px) {
    .wrap > div:first-child {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    
    .mtp-total-balance {
        margin-top: 10px;
    }
}
</style>