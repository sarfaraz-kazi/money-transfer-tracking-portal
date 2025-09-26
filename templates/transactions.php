<?php
// templates/transactions.php
if (!defined('ABSPATH')) exit;

$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
?>

<div class="wrap">
    <h1><span class="dashicons dashicons-money"></span> Transactions Management</h1>
    
    <div class="mtp-actions">
        <button type="button" class="button button-primary" id="add-transaction-btn">
            <span class="dashicons dashicons-plus"></span> Add New Transaction
        </button>
        <input type="text" id="transaction-search" placeholder="Search transactions..." style="width: 300px; margin-left: 20px;">
    </div>
    
    <!-- Transactions Table -->
    <div class="mtp-table-container">
        <div class="mtp-table-header">
            <h3>All Transactions</h3>
        </div>
        
        <?php if (!empty($transactions)): ?>
            <table class="wp-list-table widefat striped transactions-table">
                <thead>
                    <tr>
                        <th>Reference #</th>
                        <th>Date</th>
                        <th>Party</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Sender</th>
                        <th>Receiver</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td>
                            <strong class="transaction-reference"><?php echo esc_html($transaction->reference_number); ?></strong>
                        </td>
                        <td>
                            <?php echo date('M j, Y', strtotime($transaction->transaction_date)); ?>
                            <br><small><?php echo date('H:i', strtotime($transaction->transaction_date)); ?></small>
                        </td>
                        <td>
                            <strong><?php echo esc_html($transaction->party_name); ?></strong>
                        </td>
                        <td>
                            <span class="mtp-badge mtp-badge-<?php echo $transaction->transaction_type; ?>">
                                <?php echo ucfirst($transaction->transaction_type); ?>
                            </span>
                        </td>
                        <td>
                            <strong><?php echo mtp_format_currency($transaction->amount); ?></strong>
                        </td>
                        <td class="transaction-sender">
                            <?php echo $transaction->sender_name ? esc_html($transaction->sender_name) : '—'; ?>
                        </td>
                        <td class="transaction-receiver">
                            <?php echo $transaction->receiver_name ? esc_html($transaction->receiver_name) : '—'; ?>
                        </td>
                        <td class="transaction-description">
                            <?php echo $transaction->description ? esc_html($transaction->description) : '—'; ?>
                        </td>
                        <td class="mtp-table-row-actions">
                            <button type="button" class="button button-small edit-transaction-btn" data-id="<?php echo $transaction->id; ?>">
                                <span class="dashicons dashicons-edit"></span> Edit
                            </button>
                            <button type="button" class="button button-small button-link-delete delete-transaction-btn" data-id="<?php echo $transaction->id; ?>">
                                <span class="dashicons dashicons-trash"></span> Delete
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Simple Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="mtp-pagination">
                    <?php
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $class = ($i == $current_page) ? 'page-numbers current' : 'page-numbers';
                        echo '<a href="?page=mtp-transactions&paged=' . $i . '" class="' . $class . '">' . $i . '</a>';
                    }
                    ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="mtp-empty-state">
                <div class="mtp-empty-icon">
                    <span class="dashicons dashicons-money"></span>
                </div>
                <h3>No Transactions Found</h3>
                <p>Start by creating your first transaction.</p>
                <button type="button" class="button button-primary" id="add-transaction-btn">
                    <span class="dashicons dashicons-plus"></span> Add First Transaction
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Transaction Modal -->
<div id="add-transaction-modal" class="mtp-modal">
    <div class="mtp-modal-content">
        <div class="mtp-modal-header">
            <h2>Add New Transaction</h2>
            <button class="mtp-modal-close">&times;</button>
        </div>
        <form id="add-transaction-form">
            <div class="mtp-form-row">
                <div class="mtp-form-group">
                    <label for="party_id">Party *</label>
                    <div class="mtp-searchable-select">
                        <input type="text" id="party_search" placeholder="Type to search parties..." autocomplete="off">
                        <input type="hidden" id="party_id" name="party_id" required>
                        <div id="party_dropdown" class="mtp-dropdown-list" style="display: none;">
                            <?php foreach ($parties as $party): ?>
                            <div class="mtp-dropdown-item" data-value="<?php echo $party->id; ?>" data-balance="<?php echo $party->current_balance; ?>">
                                <strong><?php echo esc_html($party->party_name); ?></strong>
                                <small>Balance: <?php echo mtp_format_currency($party->current_balance); ?></small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div id="selected_party_info" style="display: none; margin-top: 8px; padding: 8px; background: #f0f8ff; border: 1px solid #b3d9ff; border-radius: 3px;">
                        <small><strong>Current Balance:</strong> <span id="party_balance"></span></small>
                    </div>
                </div>
                
                <div class="mtp-form-group">
                    <label for="transaction_type">Transaction Type *</label>
                    <select id="transaction_type" name="transaction_type" required>
                        <option value="">Select Type</option>
                        <option value="sale">Sale (Money Out)</option>
                        <option value="received">Received (Money In)</option>
                    </select>
                </div>
            </div>
            
            <div class="mtp-form-row">
                <div class="mtp-form-group">
                    <label for="amount">Amount *</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0" required>
                </div>
                <div class="mtp-form-group">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" placeholder="Optional description">
                </div>
            </div>
            
            <div class="sender-receiver-fields" style="display: none;">
                <div class="mtp-form-row">
                    <div class="mtp-form-group">
                        <label for="sender_name">Sender Name</label>
                        <input type="text" id="sender_name" name="sender_name">
                    </div>
                    
                    <div class="mtp-form-group">
                        <label for="receiver_name">Receiver Name</label>
                        <input type="text" id="receiver_name" name="receiver_name">
                    </div>
                </div>
            </div>
            
            <div class="mtp-form-actions">
                <button type="submit" class="button button-primary">Add Transaction</button>
                <button type="button" class="button mtp-modal-close">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div id="edit-transaction-modal" class="mtp-modal">
    <div class="mtp-modal-content">
        <div class="mtp-modal-header">
            <h2>Edit Transaction</h2>
            <button class="mtp-modal-close">&times;</button>
        </div>
        <form id="edit-transaction-form">
            <input type="hidden" id="edit_transaction_id" name="transaction_id">
            
            <div class="mtp-form-row">
                <div class="mtp-form-group">
                    <label for="edit_sender_name">Sender Name</label>
                    <input type="text" id="edit_sender_name" name="sender_name">
                </div>
                
                <div class="mtp-form-group">
                    <label for="edit_receiver_name">Receiver Name</label>
                    <input type="text" id="edit_receiver_name" name="receiver_name">
                </div>
            </div>
            
            <div class="mtp-form-group">
                <label for="edit_description">Description</label>
                <textarea id="edit_description" name="description" rows="3"></textarea>
            </div>
            
            <div class="mtp-form-actions">
                <button type="submit" class="button button-primary">Update Transaction</button>
                <button type="button" class="button mtp-modal-close">Cancel</button>
            </div>
        </form>
    </div>
</div>