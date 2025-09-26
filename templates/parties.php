<?php
// templates/parties.php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><span class="dashicons dashicons-groups"></span> Parties Management</h1>
    
    <div class="mtp-actions">
        <button type="button" class="button button-primary" id="add-party-btn">
            <span class="dashicons dashicons-plus"></span> Add New Party
        </button>
        <input type="text" id="party-search" placeholder="Search parties..." style="width: 300px; margin-left: 20px;">
    </div>
    
    <!-- Parties Table -->
    <div class="mtp-table-container">
        <div class="mtp-table-header">
            <h3>All Parties</h3>
        </div>
        
        <?php if (!empty($parties)): ?>
            <table class="wp-list-table widefat striped parties-table">
                <thead>
                    <tr>
                        <th>Party Name</th>
                        <th>Contact</th>
                        <th>Opening Balance</th>
                        <th>Current Balance</th>
                        <th>Total Sales</th>
                        <th>Total Received</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_opening = 0;
                    $total_current = 0;
                    $total_sales = 0;
                    $total_received = 0;
                    
                    foreach ($parties as $party): 
                        $total_opening += $party->opening_balance;
                        $total_current += $party->current_balance;
                        $total_sales += $party->total_sales;
                        $total_received += $party->total_received;
                    ?>
                    <tr>
                        <td>
                            <strong class="party-name"><?php echo esc_html($party->party_name); ?></strong>
                            <?php if ($party->email): ?>
                                <br><small class="party-email"><?php echo esc_html($party->email); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="party-contact"><?php echo esc_html($party->contact_number); ?></td>
                        <td><?php echo mtp_format_currency($party->opening_balance); ?></td>
                        <td class="<?php echo $party->current_balance < 0 ? 'mtp-negative' : ($party->current_balance > 0 ? 'mtp-positive' : 'mtp-zero'); ?>">
                            <strong><?php echo mtp_format_currency($party->current_balance); ?></strong>
                        </td>
                        <td><?php echo mtp_format_currency($party->total_sales); ?></td>
                        <td><?php echo mtp_format_currency($party->total_received); ?></td>
                        <td>
                            <span class="mtp-badge mtp-badge-<?php echo $party->status; ?>">
                                <?php echo ucfirst($party->status); ?>
                            </span>
                        </td>
                        <td class="mtp-table-row-actions">
                            <button type="button" class="button button-small edit-party-btn" data-id="<?php echo $party->id; ?>">
                                <span class="dashicons dashicons-edit"></span> Edit
                            </button>
                            <button type="button" class="button button-small button-link-delete delete-party-btn" data-id="<?php echo $party->id; ?>">
                                <span class="dashicons dashicons-trash"></span> Delete
                            </button>
                            <span class="party-address" style="display: none;"><?php echo esc_html($party->address); ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f0f0f0; font-weight: bold;">
                        <th>TOTALS:</th>
                        <th></th>
                        <th><?php echo mtp_format_currency($total_opening); ?></th>
                        <th class="<?php echo $total_current < 0 ? 'mtp-negative' : 'mtp-positive'; ?>">
                            <?php echo mtp_format_currency($total_current); ?>
                        </th>
                        <th><?php echo mtp_format_currency($total_sales); ?></th>
                        <th><?php echo mtp_format_currency($total_received); ?></th>
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
                <p>Start by adding your first party to the system.</p>
                <button type="button" class="button button-primary" id="add-party-btn">
                    <span class="dashicons dashicons-plus"></span> Add First Party
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Party Modal -->
<div id="add-party-modal" class="mtp-modal">
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
                    <label for="opening_balance">Opening Balance</label>
                    <input type="number" id="opening_balance" name="opening_balance" step="0.01" value="0">
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

<!-- Edit Party Modal -->
<div id="edit-party-modal" class="mtp-modal">
    <div class="mtp-modal-content">
        <div class="mtp-modal-header">
            <h2>Edit Party</h2>
            <button class="mtp-modal-close">&times;</button>
        </div>
        <form id="edit-party-form">
            <input type="hidden" id="edit_party_id" name="party_id">
            <div class="mtp-form-row">
                <div class="mtp-form-group">
                    <label for="edit_party_name">Party Name *</label>
                    <input type="text" id="edit_party_name" name="party_name" required>
                </div>
                <div class="mtp-form-group">
                    <label for="edit_contact_number">Contact Number</label>
                    <input type="text" id="edit_contact_number" name="contact_number">
                </div>
            </div>
            
            <div class="mtp-form-row">
                <div class="mtp-form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email">
                </div>
                <div class="mtp-form-group">
                    <label for="edit_address">Address</label>
                    <textarea id="edit_address" name="address" rows="3"></textarea>
                </div>
            </div>
            
            <div class="mtp-form-actions">
                <button type="submit" class="button button-primary">Update Party</button>
                <button type="button" class="button mtp-modal-close">Cancel</button>
            </div>
        </form>
    </div>
</div>