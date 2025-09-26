<?php
/**
 * Plugin Name: Money Transfer Tracking Portal
 * Plugin URI: https://sarfarajkazi7.link
 * Description: A simple money transfer management system similar to Excel tracking
 * Version: 1.0.0
 * Author: Sarfaraz Kazi
 * Author URI: https://sarfarajkazi7.link
 */


// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MTP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MTP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MTP_VERSION', '1.0.0');

class MoneyTransferPortal {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    public function init() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_mtp_add_party', array($this, 'ajax_add_party'));
        add_action('wp_ajax_mtp_edit_party', array($this, 'ajax_edit_party'));
        add_action('wp_ajax_mtp_delete_party', array($this, 'ajax_delete_party'));
        add_action('wp_ajax_mtp_add_transaction', array($this, 'ajax_add_transaction'));
        add_action('wp_ajax_mtp_edit_transaction', array($this, 'ajax_edit_transaction'));
        add_action('wp_ajax_mtp_delete_transaction', array($this, 'ajax_delete_transaction'));
    }
    
    public function activate() {
        $this->create_tables();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Parties Table
        $parties_table = $wpdb->prefix . 'mtp_parties';
        $sql1 = "CREATE TABLE $parties_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            party_name varchar(100) NOT NULL,
            contact_number varchar(20),
            email varchar(100),
            address text,
            opening_balance decimal(15,2) DEFAULT 0.00,
            current_balance decimal(15,2) DEFAULT 0.00,
            total_sales decimal(15,2) DEFAULT 0.00,
            total_received decimal(15,2) DEFAULT 0.00,
            status enum('active','inactive') DEFAULT 'active',
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY party_name (party_name)
        ) $charset_collate;";
        
        // Transactions Table
        $transactions_table = $wpdb->prefix . 'mtp_transactions';
        $sql2 = "CREATE TABLE $transactions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            reference_number varchar(50) NOT NULL,
            party_id mediumint(9) NOT NULL,
            transaction_type enum('sale','received') NOT NULL,
            amount decimal(15,2) NOT NULL,
            description text,
            sender_name varchar(100),
            receiver_name varchar(100),
            transaction_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY reference_number (reference_number),
            KEY party_id (party_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
    }
    
    public function admin_menu() {
        $capability = $this->get_user_capability();
        
        add_menu_page(
            'Money Transfer Portal',
            'Money Transfer',
            $capability,
            'money-transfer-portal',
            array($this, 'dashboard_page'),
            'dashicons-money-alt',
            30
        );
        
        add_submenu_page(
            'money-transfer-portal',
            'Dashboard',
            'Dashboard',
            $capability,
            'money-transfer-portal',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'money-transfer-portal',
            'Parties',
            'Parties',
            $capability,
            'mtp-parties',
            array($this, 'parties_page')
        );
        
        add_submenu_page(
            'money-transfer-portal',
            'Transactions',
            'Transactions',
            $capability,
            'mtp-transactions',
            array($this, 'transactions_page')
        );
        
        add_submenu_page(
            'money-transfer-portal',
            'Reports',
            'Reports',
            $capability,
            'mtp-reports',
            array($this, 'reports_page')
        );
    }
    
    private function get_user_capability() {
        // Allow both admin and editor roles
        if (current_user_can('manage_options')) {
            return 'manage_options'; // Administrator
        } elseif (current_user_can('edit_pages')) {
            return 'edit_pages'; // Editor
        }
        return 'manage_options'; // Default to admin only
    }
    
    private function can_user_access() {
        return current_user_can('manage_options') || current_user_can('edit_pages');
    }
    
    private function show_user_role_info() {
        $user = wp_get_current_user();
        $roles = $user->roles;
        $role_names = array_map('ucfirst', $roles);
        
        echo '<div class="mtp-user-role-info">';
        echo '<strong>Welcome, ' . esc_html($user->display_name) . '</strong> ';
        echo '<span style="margin-left: 10px;">Role: ' . implode(', ', $role_names) . '</span>';
        if (in_array('editor', $roles)) {
            echo '<span style="margin-left: 10px; color: #856404;">⚠️ Limited access - Contact administrator for additional permissions</span>';
        }
        echo '</div>';
    }
    
    public function admin_scripts($hook) {
        if (strpos($hook, 'money-transfer') === false && strpos($hook, 'mtp-') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('mtp-admin', MTP_PLUGIN_URL . 'assets/admin.js', array('jquery'), MTP_VERSION);
        wp_enqueue_style('mtp-admin', MTP_PLUGIN_URL . 'assets/admin.css', array(), MTP_VERSION);
        
        wp_localize_script('mtp-admin', 'mtp_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mtp_nonce')
        ));
    }
    
    public function dashboard_page() {
        global $wpdb;
        
        // Get statistics
        $parties_table = $wpdb->prefix . 'mtp_parties';
        $transactions_table = $wpdb->prefix . 'mtp_transactions';
        
        $total_parties = $wpdb->get_var("SELECT COUNT(*) FROM $parties_table WHERE status = 'active'");
        $total_balance = $wpdb->get_var("SELECT SUM(current_balance) FROM $parties_table WHERE status = 'active'");
        $today_transactions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $transactions_table WHERE DATE(transaction_date) = %s",
            date('Y-m-d')
        ));
        $today_amount = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $transactions_table WHERE DATE(transaction_date) = %s",
            date('Y-m-d')
        ));
        
        // Recent transactions
        $recent_transactions = $wpdb->get_results($wpdb->prepare("
            SELECT t.*, p.party_name 
            FROM $transactions_table t
            LEFT JOIN $parties_table p ON t.party_id = p.id
            ORDER BY t.transaction_date DESC
            LIMIT %d
        ", 10));
        
        include MTP_PLUGIN_PATH . 'templates/dashboard.php';
    }
    
    public function parties_page() {
        global $wpdb;
        $parties_table = $wpdb->prefix . 'mtp_parties';
        
        // Get all parties
        $parties = $wpdb->get_results("SELECT * FROM $parties_table ORDER BY party_name ASC");
        
        include MTP_PLUGIN_PATH . 'templates/parties.php';
    }
    
    public function transactions_page() {
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'mtp_transactions';
        $parties_table = $wpdb->prefix . 'mtp_parties';
        
        // Get transactions with pagination
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $transactions = $wpdb->get_results($wpdb->prepare("
            SELECT t.*, p.party_name 
            FROM $transactions_table t
            LEFT JOIN $parties_table p ON t.party_id = p.id
            ORDER BY t.transaction_date DESC
            LIMIT %d OFFSET %d
        ", $per_page, $offset));
        
        $total_transactions = $wpdb->get_var("SELECT COUNT(*) FROM $transactions_table");
        $total_pages = ceil($total_transactions / $per_page);
        
        // Get parties for dropdown
        $parties = $wpdb->get_results("SELECT id, party_name, current_balance FROM $parties_table WHERE status = 'active' ORDER BY party_name ASC");
        
        include MTP_PLUGIN_PATH . 'templates/transactions.php';
    }
    
    public function reports_page() {
        global $wpdb;
        
        $parties_table = $wpdb->prefix . 'mtp_parties';
        $transactions_table = $wpdb->prefix . 'mtp_transactions';
        
        // Get date range
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-01');
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');
        
        // Generate report data
        $report_data = null;
        if (isset($_GET['generate_report'])) {
            // Summary report
            $report_data = $wpdb->get_row($wpdb->prepare("
                SELECT 
                    COUNT(*) as total_transactions,
                    SUM(CASE WHEN transaction_type = 'sale' THEN amount ELSE 0 END) as total_sales,
                    SUM(CASE WHEN transaction_type = 'received' THEN amount ELSE 0 END) as total_received,
                    COUNT(DISTINCT party_id) as active_parties
                FROM $transactions_table 
                WHERE DATE(transaction_date) BETWEEN %s AND %s
            ", $date_from, $date_to));
            
            // Party wise data
            $party_data = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    p.party_name,
                    p.current_balance,
                    SUM(CASE WHEN t.transaction_type = 'sale' THEN t.amount ELSE 0 END) as period_sales,
                    SUM(CASE WHEN t.transaction_type = 'received' THEN t.amount ELSE 0 END) as period_received,
                    COUNT(t.id) as transaction_count
                FROM $parties_table p
                LEFT JOIN $transactions_table t ON p.id = t.party_id 
                    AND DATE(t.transaction_date) BETWEEN %s AND %s
                WHERE p.status = 'active'
                GROUP BY p.id, p.party_name, p.current_balance
                ORDER BY p.party_name
            ", $date_from, $date_to));
            
            $report_data->party_data = $party_data;
        }
        
        include MTP_PLUGIN_PATH . 'templates/reports.php';
    }
    
    // AJAX Handlers
    public function ajax_add_party() {
        check_ajax_referer('mtp_nonce', 'nonce');
        
        if (!$this->can_user_access()) {
            wp_send_json_error('Access denied');
            return;
        }
        
        global $wpdb;
        $parties_table = $wpdb->prefix . 'mtp_parties';
        
        $party_name = sanitize_text_field($_POST['party_name']);
        $contact_number = sanitize_text_field($_POST['contact_number']);
        $email = sanitize_email($_POST['email']);
        $address = sanitize_textarea_field($_POST['address']);
        $opening_balance = floatval($_POST['opening_balance']);
        
        $result = $wpdb->insert(
            $parties_table,
            array(
                'party_name' => $party_name,
                'contact_number' => $contact_number,
                'email' => $email,
                'address' => $address,
                'opening_balance' => $opening_balance,
                'current_balance' => $opening_balance
            ),
            array('%s', '%s', '%s', '%s', '%f', '%f')
        );
        
        if ($result) {
            wp_send_json_success('Party added successfully');
        } else {
            wp_send_json_error('Failed to add party');
        }
    }
    
    public function ajax_edit_party() {
        check_ajax_referer('mtp_nonce', 'nonce');
        
        if (!$this->can_user_access()) {
            wp_send_json_error('Access denied');
            return;
        }
        
        global $wpdb;
        $parties_table = $wpdb->prefix . 'mtp_parties';
        
        $party_id = intval($_POST['party_id']);
        $party_name = sanitize_text_field($_POST['party_name']);
        $contact_number = sanitize_text_field($_POST['contact_number']);
        $email = sanitize_email($_POST['email']);
        $address = sanitize_textarea_field($_POST['address']);
        
        $result = $wpdb->update(
            $parties_table,
            array(
                'party_name' => $party_name,
                'contact_number' => $contact_number,
                'email' => $email,
                'address' => $address
            ),
            array('id' => $party_id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('Party updated successfully');
        } else {
            wp_send_json_error('Failed to update party');
        }
    }
    
    public function ajax_delete_party() {
        check_ajax_referer('mtp_nonce', 'nonce');
        
        // Only administrators can delete parties
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Only administrators can delete parties');
            return;
        }
        
        global $wpdb;
        $parties_table = $wpdb->prefix . 'mtp_parties';
        
        $party_id = intval($_POST['party_id']);
        
        $result = $wpdb->delete($parties_table, array('id' => $party_id), array('%d'));
        
        if ($result) {
            wp_send_json_success('Party deleted successfully');
        } else {
            wp_send_json_error('Failed to delete party');
        }
    }
    
    public function ajax_add_transaction() {
        check_ajax_referer('mtp_nonce', 'nonce');
        
        if (!$this->can_user_access()) {
            wp_send_json_error('Access denied');
            return;
        }
        
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'mtp_transactions';
        $parties_table = $wpdb->prefix . 'mtp_parties';
        
        $party_id = intval($_POST['party_id']);
        $transaction_type = sanitize_text_field($_POST['transaction_type']);
        $amount = floatval($_POST['amount']);
        $description = sanitize_textarea_field($_POST['description']);
        $sender_name = sanitize_text_field($_POST['sender_name']);
        $receiver_name = sanitize_text_field($_POST['receiver_name']);
        $reference_number = 'MTP' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Insert transaction
            $result = $wpdb->insert(
                $transactions_table,
                array(
                    'reference_number' => $reference_number,
                    'party_id' => $party_id,
                    'transaction_type' => $transaction_type,
                    'amount' => $amount,
                    'description' => $description,
                    'sender_name' => $sender_name,
                    'receiver_name' => $receiver_name
                ),
                array('%s', '%d', '%s', '%f', '%s', '%s', '%s')
            );
            
            if (!$result) {
                throw new Exception('Failed to insert transaction');
            }
            
            // Update party balance
            if ($transaction_type === 'sale') {
                $wpdb->query($wpdb->prepare("
                    UPDATE $parties_table 
                    SET current_balance = current_balance - %f,
                        total_sales = total_sales + %f
                    WHERE id = %d
                ", $amount, $amount, $party_id));
            } elseif ($transaction_type === 'received') {
                $wpdb->query($wpdb->prepare("
                    UPDATE $parties_table 
                    SET current_balance = current_balance + %f,
                        total_received = total_received + %f
                    WHERE id = %d
                ", $amount, $amount, $party_id));
            }
            
            $wpdb->query('COMMIT');
            wp_send_json_success('Transaction added successfully');
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error('Failed to add transaction: ' . $e->getMessage());
        }
    }
    
    public function ajax_edit_transaction() {
        check_ajax_referer('mtp_nonce', 'nonce');
        
        if (!$this->can_user_access()) {
            wp_send_json_error('Access denied');
            return;
        }
        
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'mtp_transactions';
        
        $transaction_id = intval($_POST['transaction_id']);
        $description = sanitize_textarea_field($_POST['description']);
        $sender_name = sanitize_text_field($_POST['sender_name']);
        $receiver_name = sanitize_text_field($_POST['receiver_name']);
        
        $result = $wpdb->update(
            $transactions_table,
            array(
                'description' => $description,
                'sender_name' => $sender_name,
                'receiver_name' => $receiver_name
            ),
            array('id' => $transaction_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('Transaction updated successfully');
        } else {
            wp_send_json_error('Failed to update transaction');
        }
    }
    
    public function ajax_delete_transaction() {
        check_ajax_referer('mtp_nonce', 'nonce');
        
        // Only administrators can delete transactions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Only administrators can delete transactions');
            return;
        }
        
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'mtp_transactions';
        $parties_table = $wpdb->prefix . 'mtp_parties';
        
        $transaction_id = intval($_POST['transaction_id']);
        
        // Get transaction details first
        $transaction = $wpdb->get_row($wpdb->prepare("SELECT * FROM $transactions_table WHERE id = %d", $transaction_id));
        
        if (!$transaction) {
            wp_send_json_error('Transaction not found');
            return;
        }
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Reverse the balance update
            if ($transaction->transaction_type === 'sale') {
                $wpdb->query($wpdb->prepare("
                    UPDATE $parties_table 
                    SET current_balance = current_balance + %f,
                        total_sales = total_sales - %f
                    WHERE id = %d
                ", $transaction->amount, $transaction->amount, $transaction->party_id));
            } elseif ($transaction->transaction_type === 'received') {
                $wpdb->query($wpdb->prepare("
                    UPDATE $parties_table 
                    SET current_balance = current_balance - %f,
                        total_received = total_received - %f
                    WHERE id = %d
                ", $transaction->amount, $transaction->amount, $transaction->party_id));
            }
            
            // Delete transaction
            $result = $wpdb->delete($transactions_table, array('id' => $transaction_id), array('%d'));
            
            if (!$result) {
                throw new Exception('Failed to delete transaction');
            }
            
            $wpdb->query('COMMIT');
            wp_send_json_success('Transaction deleted successfully');
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error('Failed to delete transaction: ' . $e->getMessage());
        }
    }
}

// Initialize the plugin
new MoneyTransferPortal();

// Helper Functions
function mtp_format_currency($amount) {
    return '₹' . number_format($amount, 2);
}
?>