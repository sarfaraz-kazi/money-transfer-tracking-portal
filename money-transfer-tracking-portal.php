<?php
/**
 * Plugin Name: Money Transfer Portal
 * Plugin URI: https://sarfarajkazi7.link
 * Description: Simple money transfer management system for parties, transactions, and reports.
 * Version: 1.0.0
 * Author: Sarfaraz Kazi
 * Author URI: https://sarfarajkazi7.link
 * License: GPL v2 or later
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
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action( 'login_enqueue_scripts', array($this, 'my_custom_login_styles'));
        add_action('template_redirect', array($this, 'redirect_frontend_to_admin'));
        // AJAX handlers
        add_action('wp_ajax_mtp_add_party', array($this, 'ajax_add_party'));
        add_action('wp_ajax_mtp_delete_party', array($this, 'ajax_delete_party'));
        add_action('wp_ajax_mtp_migrate_party', array($this, 'ajax_migrate_party'));
        add_action('wp_ajax_mtp_quick_send', array($this, 'ajax_quick_send'));
        add_action('wp_ajax_mtp_quick_receive', array($this, 'ajax_quick_receive'));
        add_action('wp_ajax_mtp_add_transaction', array($this, 'ajax_add_transaction'));
        add_action('wp_ajax_mtp_edit_transaction', array($this, 'ajax_edit_transaction'));
        add_action('wp_ajax_mtp_delete_transaction', array($this, 'ajax_delete_transaction'));
        add_action('wp_ajax_mtp_update_database', array($this, 'ajax_update_database'));
        add_action('wp_ajax_mtp_get_party_balance', array($this, 'ajax_get_party_balance'));
        add_action('wp_ajax_mtp_export_parties', array($this, 'ajax_export_parties'));
        add_action('wp_ajax_mtp_generate_report', array($this, 'ajax_generate_report'));
        add_action('wp_ajax_mtp_export_report', array($this, 'ajax_export_report'));
    }
    
    public function activate() {
        $this->create_tables();
        $this->set_default_options();
    }
    
    public function deactivate() {
        // Cleanup if needed
    }
    public function redirect_frontend_to_admin() {

        if (!is_admin() && !is_login_page()) {
            if (is_user_logged_in()) {
                wp_redirect(admin_url());
                exit;
            } else {
                wp_redirect(wp_login_url());
                exit;
            }
        }
    }
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Parties Table - Complete structure
        $parties_table = $wpdb->prefix . 'mtp_parties';
        $sql1 = "CREATE TABLE $parties_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            party_name varchar(100) NOT NULL,
            contact_number varchar(20),
            email varchar(100),
            address text,
            previous_balance decimal(15,2) DEFAULT 0.00,
            today_send decimal(15,2) DEFAULT 0.00,
            today_receive decimal(15,2) DEFAULT 0.00,
            current_balance decimal(15,2) DEFAULT 0.00,
            total_send decimal(15,2) DEFAULT 0.00,
            total_receive decimal(15,2) DEFAULT 0.00,
            last_transaction_date date,
            status enum('active','inactive') DEFAULT 'active',
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY party_name (party_name),
            INDEX idx_status (status),
            INDEX idx_last_transaction (last_transaction_date)
        ) $charset_collate;";
        
        // Daily Balances History Table
        $daily_balances_table = $wpdb->prefix . 'mtp_daily_balances';
        $sql2 = "CREATE TABLE $daily_balances_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            party_id mediumint(9) NOT NULL,
            transaction_date date NOT NULL,
            opening_balance decimal(15,2) DEFAULT 0.00,
            total_send decimal(15,2) DEFAULT 0.00,
            total_receive decimal(15,2) DEFAULT 0.00,
            closing_balance decimal(15,2) DEFAULT 0.00,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY party_date (party_id, transaction_date),
            FOREIGN KEY (party_id) REFERENCES $parties_table(id) ON DELETE CASCADE,
            INDEX idx_date (transaction_date)
        ) $charset_collate;";
        
        // Transactions Table
        $transactions_table = $wpdb->prefix . 'mtp_transactions';
        $sql3 = "CREATE TABLE $transactions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            party_id mediumint(9) NOT NULL,
            transaction_type enum('send','receive') NOT NULL,
            amount decimal(15,2) NOT NULL,
            description text,
            sender_name varchar(100),
            receiver_name varchar(100),
            transaction_date date NOT NULL,
            transaction_time time DEFAULT NULL,
            reference_number varchar(50),
            status enum('pending','completed','cancelled') DEFAULT 'completed',
            created_by mediumint(9),
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (party_id) REFERENCES $parties_table(id) ON DELETE CASCADE,
            INDEX idx_party_date (party_id, transaction_date),
            INDEX idx_reference (reference_number),
            INDEX idx_status (status),
            INDEX idx_created_by (created_by)
        ) $charset_collate;";
        
        // System Settings Table
        $settings_table = $wpdb->prefix . 'mtp_settings';
        $sql4 = "CREATE TABLE $settings_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value longtext,
            autoload enum('yes','no') DEFAULT 'yes',
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
        dbDelta($sql4);
        
    }
    
    private function set_default_options() {
        $default_settings = array(
            'mtp_business_name' => 'Money Transfer Portal',
            'mtp_default_currency' => 'INR',
            'mtp_reference_prefix' => 'MTP',
            'mtp_backup_frequency' => 'daily',
            'mtp_enable_notifications' => '1',
            'mtp_date_format' => 'Y-m-d',
            'mtp_timezone' => 'Asia/Kolkata'
        );
        
        foreach ($default_settings as $key => $value) {
            if (!get_option($key)) {
                add_option($key, $value);
            }
        }
    }
    
    public function admin_menu() {
        $capability = $this->get_user_capability();
        
        add_menu_page(
            'Money Transfer Portal',
            'Money Transfer',
            $capability,
            'money-transfer-portal',
            array($this, 'parties_page'),
            'dashicons-money-alt',
            30
        );
        
        add_submenu_page(
            'money-transfer-portal',
            'Parties',
            'Parties',
            $capability,
            'money-transfer-portal',
            array($this, 'parties_page')
        );

        /*
        add_submenu_page(
            'money-transfer-portal',
            'Dashboard',
            'Dashboard',
            $capability,
            'money-transfer-portal',
            array($this, 'dashboard_page')
        );
        */
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
        if (current_user_can('manage_options')) {
            return 'manage_options';
        } elseif (current_user_can('edit_pages')) {
            return 'edit_pages';
        }
        return 'manage_options';
    }
    
    private function can_user_access() {
        return current_user_can('manage_options') || current_user_can('edit_pages');
    }
    
    public function admin_scripts($hook) {
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
        
        wp_enqueue_script('mtp-admin', MTP_PLUGIN_URL . 'assets/admin.js', array('jquery'), MTP_VERSION, true);
        wp_enqueue_style('mtp-admin', MTP_PLUGIN_URL . 'assets/admin.css', array(), MTP_VERSION);
        
        wp_localize_script('mtp-admin', 'mtp_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mtp_nonce'),
            'current_user_id' => get_current_user_id(),
            'currency_symbol' => get_option('mtp_default_currency', 'INR')
        ));
    }

    // Add custom CSS to WP login page
    public function my_custom_login_styles() {
        wp_enqueue_style( 'custom-login', MTP_PLUGIN_URL . 'assets/custom-login.css' );
    }


    public function dashboard_page() {
        global $wpdb;
        
        $parties_table = $wpdb->prefix . 'mtp_parties';
        $transactions_table = $wpdb->prefix . 'mtp_transactions';
        
        // Get summary statistics
        $total_parties = $wpdb->get_var("SELECT COUNT(*) FROM $parties_table WHERE status = 'active'");
        $total_balance = $wpdb->get_var("SELECT SUM(COALESCE(current_balance, 0)) FROM $parties_table WHERE status = 'active'");
        
        $today = date('Y-m-d');
        $today_transactions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $transactions_table WHERE transaction_date = %s",
            $today
        ));
        $today_amount = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $transactions_table WHERE transaction_date = %s",
            $today
        ));
        
        // Recent transactions
        $recent_transactions = $wpdb->get_results($wpdb->prepare("
            SELECT t.*, p.party_name 
            FROM $transactions_table t
            LEFT JOIN $parties_table p ON t.party_id = p.id
            ORDER BY t.created_date DESC
            LIMIT %d
        ", 10));
        
        // Top parties by absolute balance
        $top_parties = $wpdb->get_results("
            SELECT party_name, current_balance, total_send, total_receive
            FROM $parties_table 
            WHERE status = 'active'
            ORDER BY ABS(current_balance) DESC
            LIMIT 10
        ");
        
        include MTP_PLUGIN_PATH . 'templates/dashboard.php';
    }
    
    public function parties_page() {
        global $wpdb;
        $parties_table = $wpdb->prefix . 'mtp_parties';
        $transactions_table = $wpdb->prefix . 'mtp_transactions';
        
        $today = date('Y-m-d');
        
        // Check if we need to reset daily balances (new day)
        $this->check_daily_reset();
        
        // Get all parties
        $parties = $wpdb->get_results("
    SELECT *,
        COALESCE(previous_balance, 0) as previous_balance,
        COALESCE(today_send, 0) as today_send,
        COALESCE(today_receive, 0) as today_receive,
        COALESCE(current_balance, 0) as current_balance
    FROM $parties_table
    WHERE status = 'active'
    ORDER BY previous_balance ASC, id ASC
");
        
        // Calculate today's transactions for each party
        foreach ($parties as $party) {
            $today_transactions = $wpdb->get_row($wpdb->prepare("
                SELECT 
                    COALESCE(SUM(CASE WHEN transaction_type = 'send' THEN amount ELSE 0 END), 0) as today_send,
                    COALESCE(SUM(CASE WHEN transaction_type = 'receive' THEN amount ELSE 0 END), 0) as today_receive
                FROM $transactions_table 
                WHERE party_id = %d AND transaction_date = %s
            ", $party->id, $today));
            
            if ($today_transactions) {
                $party->today_send = $today_transactions->today_send;
                $party->today_receive = $today_transactions->today_receive;
            }

            // Calculate current balance
            if(!isZero($party->today_receive) || !isZero($party->today_send)){
                $current_balance = $party->previous_balance + $party->today_receive - $party->today_send;

                // Update the party record
                $wpdb->update(
                    $parties_table,
                    array(
                        'today_send' => $party->today_send,
                        'today_receive' => $party->today_receive,
                        'current_balance' => $current_balance,
                        'last_transaction_date' => $today
                    ),
                    array('id' => $party->id),
                    array('%f', '%f', '%f', '%s'),
                    array('%d')
                );
            } else {
                // Only update if current_balance is not already set (to preserve migrations)
                if (isZero($party->current_balance)) {
                    $current_balance = 0;

                    $wpdb->update(
                        $parties_table,
                        array(
                            'current_balance' => $current_balance,
                            'last_transaction_date' => $today
                        ),
                        array('id' => $party->id),
                        array('%f', '%s'),
                        array('%d')
                    );
                } else {
                    // Keep existing current_balance (for migrated parties)
                    $current_balance = $party->current_balance;
                }
            }

// Update the party object
            $party->current_balance = $current_balance;
        }
        
        include MTP_PLUGIN_PATH . 'templates/parties.php';
    }
    
    private function check_daily_reset() {
        global $wpdb;
        $parties_table = $wpdb->prefix . 'mtp_parties';
        $daily_balances_table = $wpdb->prefix . 'mtp_daily_balances';
        
        $today = date('Y-m-d');
        
        // Get parties that need daily reset
        $parties_to_reset = $wpdb->get_results($wpdb->prepare("
            SELECT id, party_name, current_balance, last_transaction_date, 
                   COALESCE(today_send, 0) as today_send, 
                   COALESCE(today_receive, 0) as today_receive
            FROM $parties_table 
            WHERE status = 'active' 
            AND (last_transaction_date IS NULL OR last_transaction_date < %s)
        ", $today));
        
        foreach ($parties_to_reset as $party) {
            if ($party->last_transaction_date && $party->last_transaction_date !== $today) {
                // Save yesterday's data to history
                $wpdb->replace(
                    $daily_balances_table,
                    array(
                        'party_id' => $party->id,
                        'transaction_date' => $party->last_transaction_date,
                        'opening_balance' => $party->current_balance - $party->today_receive + $party->today_send,
                        'total_send' => $party->today_send,
                        'total_receive' => $party->today_receive,
                        'closing_balance' => $party->current_balance
                    ),
                    array('%d', '%s', '%f', '%f', '%f', '%f')
                );
            }
            
            // Reset for new day - yesterday's closing becomes today's opening
            $parties_update_array = array(
                    'previous_balance' => $party->current_balance,
                    'today_send' => 0,
                    'today_receive' => 0,
                    'last_transaction_date' => $today
            );
            if(isZero($party->current_balance)){
                unset($parties_update_array['previous_balance']);
            }
            $wpdb->update(
                $parties_table,
                $parties_update_array,
                array('id' => $party->id),
                array('%f', '%f', '%f', '%s'),
                array('%d')
            );
        }
    }
    
    public function transactions_page() {
        
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'mtp_transactions';
        $parties_table = $wpdb->prefix . 'mtp_parties';
        
        // Handle filters
        $where_conditions = array('1=1');
        $where_values = array();
        
        $filter_party = isset($_GET['filter_party']) ? intval($_GET['filter_party']) : 0;
        $filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : '';
        $filter_date_from = isset($_GET['filter_date_from']) ? sanitize_text_field($_GET['filter_date_from']) : '';
        $filter_date_to = isset($_GET['filter_date_to']) ? sanitize_text_field($_GET['filter_date_to']) : '';
        

        if ($filter_party) {
            $where_conditions[] = 't.party_id = %d';
            $where_values[] = $filter_party;
        }
        if ($filter_type) {
            $where_conditions[] = 't.transaction_type = %s';
            $where_values[] = $filter_type;
        }
        if ($filter_date_from) {
            $where_conditions[] = 't.transaction_date >= %s';
            $where_values[] = $filter_date_from;
        }
        if ($filter_date_to) {
            $where_conditions[] = 't.transaction_date <= %s';
            $where_values[] = $filter_date_to;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Get transactions with pagination
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 50;
        $offset = ($page - 1) * $per_page;
        
        $query = "
            SELECT t.*, p.party_name 
            FROM $transactions_table t
            LEFT JOIN $parties_table p ON t.party_id = p.id
            WHERE $where_clause
            ORDER BY t.transaction_date DESC, t.created_date DESC
            LIMIT %d OFFSET %d
        ";
        
        $where_values[] = $per_page;
        $where_values[] = $offset;
        
        $transactions = $wpdb->get_results($wpdb->prepare($query, $where_values));
        
        // Get total count for pagination
        $count_query = "SELECT COUNT(*) FROM $transactions_table t WHERE $where_clause";

        // Only use wpdb->prepare if we have parameters for the WHERE clause
        $count_params = array_slice($where_values, 0, -2);
        if (!empty($count_params)) {
            $total_transactions = $wpdb->get_var($wpdb->prepare($count_query, $count_params));
        } else {
            // No parameters needed, execute directly
            $total_transactions = $wpdb->get_var($count_query);
        }
        $total_pages = ceil($total_transactions / $per_page);
        // Get parties for dropdown
        $parties = $wpdb->get_results("SELECT id, party_name,current_balance FROM $parties_table WHERE status = 'active' ORDER BY party_name ASC");
        include MTP_PLUGIN_PATH . 'templates/transactions.php';
    }
    
    public function reports_page() {
        global $wpdb;
        
        $parties_table = $wpdb->prefix . 'mtp_parties';
        $transactions_table = $wpdb->prefix . 'mtp_transactions';
        
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-01');
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');
        $report_type = isset($_GET['report_type']) ? sanitize_text_field($_GET['report_type']) : 'summary';
        
        $report_data = null;
        if (isset($_GET['generate_report'])) {
            // Generate report based on type
            switch ($report_type) {
                case 'summary':
                    $report_data = $this->generate_summary_report($date_from, $date_to);
                    break;
                case 'party_wise':
                    $report_data = $this->generate_party_wise_report($date_from, $date_to);
                    break;
                case 'daily':
                    $report_data = $this->generate_daily_report($date_from, $date_to);
                    break;
                default:
                    $report_data = $this->generate_summary_report($date_from, $date_to);
            }
        }
        
        include MTP_PLUGIN_PATH . 'templates/reports.php';
    }
    
    private function generate_summary_report($date_from, $date_to) {
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'mtp_transactions';
        $parties_table = $wpdb->prefix . 'mtp_parties';
        
        $summary = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_transactions,
                SUM(CASE WHEN transaction_type = 'send' THEN amount ELSE 0 END) as total_sales,
                SUM(CASE WHEN transaction_type = 'receive' THEN amount ELSE 0 END) as total_received,
                COUNT(DISTINCT party_id) as active_parties
            FROM $transactions_table 
            WHERE transaction_date BETWEEN %s AND %s
        ", $date_from, $date_to));
        
        $party_data = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.id,p.party_name,
                COALESCE(p.current_balance, 0) as current_balance,
                COALESCE(SUM(CASE WHEN t.transaction_type = 'send' THEN t.amount ELSE 0 END), 0) as period_sales,
                COALESCE(SUM(CASE WHEN t.transaction_type = 'receive' THEN t.amount ELSE 0 END), 0) as period_received,
                COUNT(t.id) as transaction_count
            FROM $parties_table p
            LEFT JOIN $transactions_table t ON p.id = t.party_id 
                AND t.transaction_date BETWEEN %s AND %s
            WHERE p.status = 'active'
            GROUP BY p.id, p.party_name, p.current_balance
            HAVING transaction_count > 0 OR p.current_balance != 0
            ORDER BY p.party_name
        ", $date_from, $date_to));
        
        $summary->party_data = $party_data;
        return $summary;
    }
    
    private function generate_party_wise_report($date_from, $date_to) {
        // Similar to summary but more detailed per party
        return $this->generate_summary_report($date_from, $date_to);
    }
    
    private function generate_daily_report($date_from, $date_to) {
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'mtp_transactions';
        
        $daily_data = $wpdb->get_results($wpdb->prepare("
            SELECT 
                transaction_date,
                SUM(CASE WHEN transaction_type = 'send' THEN amount ELSE 0 END) as daily_send,
                SUM(CASE WHEN transaction_type = 'receive' THEN amount ELSE 0 END) as daily_receive,
                COUNT(*) as daily_transactions
            FROM $transactions_table 
            WHERE transaction_date BETWEEN %s AND %s
            GROUP BY transaction_date
            ORDER BY transaction_date DESC
        ", $date_from, $date_to));
        
        $report = new stdClass();
        $report->daily_data = $daily_data;
        $report->total_transactions = array_sum(array_column($daily_data, 'daily_transactions'));
        $report->total_sales = array_sum(array_column($daily_data, 'daily_send'));
        $report->total_received = array_sum(array_column($daily_data, 'daily_receive'));
        
        return $report;
    }

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
        $previous_balance = floatval($_POST['previous_balance']);
        
        $result = $wpdb->insert(
            $parties_table,
            array(
                'party_name' => $party_name,
                'contact_number' => $contact_number,
                'email' => $email,
                'address' => $address,
                'previous_balance' => $previous_balance,
                'current_balance' => 0,
                'last_transaction_date' => date('Y-m-d')
            ),
            array('%s', '%s', '%s', '%s', '%f', '%f', '%s')
        );
        
        if ($result) {
            wp_send_json_success('Party added successfully');
        } else {
            wp_send_json_error('Failed to add party');
        }
    }

    public function ajax_migrate_party(){
        check_ajax_referer('mtp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Only administrators can migrate parties');
            return;
        }

        global $wpdb;
        $parties_table = $wpdb->prefix . 'mtp_parties';

        $party_id = intval($_POST['party_id']);

        try {
            // Get the previous_balance value
            $previous_balance = $wpdb->get_var($wpdb->prepare("
        SELECT previous_balance 
        FROM $parties_table 
        WHERE id = %d
    ", $party_id));

            if ($previous_balance === null) {
                wp_send_json_error('Party not found');
                return;
            }

            // Update: move previous_balance to current_balance, reset previous_balance
            // Also update last_transaction_date to prevent recalculation
            $result = $wpdb->update(
                $parties_table,
                array(
                    'current_balance' => $previous_balance,
                    'previous_balance' => 0,
                    'today_send' => 0,
                    'today_receive' => 0,
                    'last_transaction_date' => date('Y-m-d')
                ),
                array('id' => $party_id),
                array('%f', '%f', '%f', '%f', '%s'),
                array('%d')
            );

            if ($result === false) {
                wp_send_json_error('Update failed: ' . $wpdb->last_error);
                return;
            }
            wp_send_json_success('Party migrated successfully');
        } catch (Exception $e) {
            wp_send_json_error('Failed to migrate: ' . $e->getMessage());
        }
    }

    public function ajax_delete_party() {
        check_ajax_referer('mtp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Only administrators can delete parties');
            return;
        }
        
        global $wpdb;
        $parties_table = $wpdb->prefix . 'mtp_parties';
        $transactions_table = $wpdb->prefix . 'mtp_transactions';
        
        $party_id = intval($_POST['party_id']);

        $entered_pin = sanitize_text_field($_POST['pin']);

        $correct_pin = '654321';

        // Verify PIN
        if ($entered_pin !== $correct_pin) {
            wp_send_json_error('Incorrect PIN. Deletion cancelled.');
            return;
        }
        
        // Check if party has transactions
        $transaction_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $transactions_table WHERE party_id = %d",
            $party_id
        ));
        
        if ($transaction_count > 0) {
            wp_send_json_error('Cannot delete party with existing transactions');
            return;
        }
        
        $result = $wpdb->delete($parties_table, array('id' => $party_id), array('%d'));
        
        if ($result) {
            wp_send_json_success('Party deleted successfully');
        } else {
            wp_send_json_error('Failed to delete party');
        }
    }
    
    public function ajax_quick_send() {
        check_ajax_referer('mtp_nonce', 'nonce');
        
        if (!$this->can_user_access()) {
            wp_send_json_error('Access denied');
            return;
        }
        
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'mtp_transactions';
        $parties_table = $wpdb->prefix . 'mtp_parties';
        
        $party_id = intval($_POST['party_id']);
        $amount = floatval($_POST['amount']);
        $description = sanitize_textarea_field($_POST['description']);
        $receiver_name = sanitize_text_field($_POST['receiver_name']);
        $today = date('Y-m-d');
        
        if ($amount <= 0) {
            wp_send_json_error('Amount must be greater than zero');
            return;
        }
        
        // Generate reference number
        $reference_number = $this->generate_reference_number();
        
        $wpdb->query('START TRANSACTION');
        
        try {
            // Insert transaction
            $result = $wpdb->insert(
                $transactions_table,
                array(
                    'party_id' => $party_id,
                    'transaction_type' => 'send',
                    'amount' => $amount,
                    'description' => $description,
                    'receiver_name' => $receiver_name,
                    'transaction_date' => $today,
                    'transaction_time' => current_time('H:i:s'),
                    'reference_number' => $reference_number,
                    'created_by' => get_current_user_id(),
                    'created_date' => current_time('mysql')
                ),
                array('%d', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
            );
            
            if (!$result) {
                throw new Exception('Failed to insert transaction');
            }
            
            // Update party balance
            $wpdb->query($wpdb->prepare("
                UPDATE $parties_table 
                SET today_send = today_send + %f,
                    total_send = total_send + %f,
                    current_balance = previous_balance + today_receive - (today_send + %f),
                    last_transaction_date = %s,
                    updated_date = %s
                WHERE id = %d
            ", $amount, $amount, $amount, $today, current_time('mysql'), $party_id));
            
            $wpdb->query('COMMIT');
            
            $updated_balance = $wpdb->get_var($wpdb->prepare("SELECT current_balance FROM $parties_table WHERE id = %d", $party_id));
            
            wp_send_json_success(array(
                'message' => 'Send transaction added successfully',
                'new_balance' => $updated_balance,
                'reference' => $reference_number
            ));
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error('Failed to add send transaction: ' . $e->getMessage());
        }
    }
    
    public function ajax_quick_receive() {
        check_ajax_referer('mtp_nonce', 'nonce');
        
        if (!$this->can_user_access()) {
            wp_send_json_error('Access denied');
            return;
        }
        
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'mtp_transactions';
        $parties_table = $wpdb->prefix . 'mtp_parties';
        
        $party_id = intval($_POST['party_id']);
        $amount = floatval($_POST['amount']);
        $description = sanitize_textarea_field($_POST['description']);
        $sender_name = sanitize_text_field($_POST['sender_name']);
        $today = date('Y-m-d');
        
        if ($amount <= 0) {
            wp_send_json_error('Amount must be greater than zero');
            return;
        }
        
        // Generate reference number
        $reference_number = $this->generate_reference_number();
        
        $wpdb->query('START TRANSACTION');
        
        try {
            // Insert transaction
            $result = $wpdb->insert(
                $transactions_table,
                array(
                    'party_id' => $party_id,
                    'transaction_type' => 'receive',
                    'amount' => $amount,
                    'description' => $description,
                    'sender_name' => $sender_name,
                    'transaction_date' => $today,
                    'transaction_time' => current_time('H:i:s'),
                    'reference_number' => $reference_number,
                    'created_by' => get_current_user_id(),
                    'created_date' => current_time('mysql')
                ),
                array('%d', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
            );
            
            if (!$result) {
                throw new Exception('Failed to insert transaction');
            }
            
            // Update party balance
            $wpdb->query($wpdb->prepare("
                UPDATE $parties_table 
                SET today_receive = today_receive + %f,
                    total_receive = total_receive + %f,
                    current_balance = previous_balance + (today_receive + %f) - today_send,
                    last_transaction_date = %s,
                    updated_date = %s
                WHERE id = %d
            ", $amount, $amount, $amount, $today, current_time('mysql'), $party_id));
            
            $wpdb->query('COMMIT');
            
            $updated_balance = $wpdb->get_var($wpdb->prepare("SELECT current_balance FROM $parties_table WHERE id = %d", $party_id));
            
            wp_send_json_success(array(
                'message' => 'Receive transaction added successfully',
                'new_balance' => $updated_balance,
                'reference' => $reference_number
            ));
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error('Failed to add receive transaction: ' . $e->getMessage());
        }
    }
    
    private function generate_reference_number() {
        $prefix = get_option('mtp_reference_prefix', 'MTP');
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $date . $random;
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
        $today = date('Y-m-d');
        
        if ($amount <= 0) {
            wp_send_json_error('Amount must be greater than zero');
            return;
        }
        
        $reference_number = $this->generate_reference_number();
        
        $wpdb->query('START TRANSACTION');
        
        try {
            $result = $wpdb->insert(
                $transactions_table,
                array(
                    'party_id' => $party_id,
                    'transaction_type' => $transaction_type,
                    'amount' => $amount,
                    'description' => $description,
                    'sender_name' => $sender_name,
                    'receiver_name' => $receiver_name,
                    'transaction_date' => $today,
                    'transaction_time' => current_time('H:i:s'),
                    'reference_number' => $reference_number,
                    'created_by' => get_current_user_id()
                ),
                array('%d', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
            );
            
            if (!$result) {
                throw new Exception('Failed to insert transaction');
            }
            
            // Update party balance
            if ($transaction_type === 'send') {
                $wpdb->query($wpdb->prepare("
                    UPDATE $parties_table 
                    SET today_send = today_send + %f,
                        total_send = total_send + %f,
                        current_balance = previous_balance + today_receive - (today_send + %f),
                        last_transaction_date = %s
                    WHERE id = %d
                ", $amount, $amount, $amount, $today, $party_id));
            } else {
                $wpdb->query($wpdb->prepare("
                    UPDATE $parties_table 
                    SET today_receive = today_receive + %f,
                        total_receive = total_receive + %f,
                        current_balance = previous_balance + (today_receive + %f) - today_send,
                        last_transaction_date = %s
                    WHERE id = %d
                ", $amount, $amount, $amount, $today, $party_id));
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
                'receiver_name' => $receiver_name,
                'updated_date' => current_time('mysql')
            ),
            array('id' => $transaction_id),
            array('%s', '%s', '%s', '%s'),
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
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Only administrators can delete transactions');
            return;
        }
        
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'mtp_transactions';
        $parties_table = $wpdb->prefix . 'mtp_parties';
        
        $transaction_id = intval($_POST['transaction_id']);
        
        $transaction = $wpdb->get_row($wpdb->prepare("SELECT * FROM $transactions_table WHERE id = %d", $transaction_id));
        
        if (!$transaction) {
            wp_send_json_error('Transaction not found');
            return;
        }
        
        $wpdb->query('START TRANSACTION');
        
        try {
            // Reverse the balance changes
            if ($transaction->transaction_type === 'send') {
                $wpdb->query($wpdb->prepare("
                    UPDATE $parties_table 
                    SET today_send = today_send - %f,
                        total_send = total_send - %f,
                        current_balance = previous_balance + today_receive - (today_send - %f)
                    WHERE id = %d
                ", $transaction->amount, $transaction->amount, $transaction->amount, $transaction->party_id));
            } else {
                $wpdb->query($wpdb->prepare("
                    UPDATE $parties_table 
                    SET today_receive = today_receive - %f,
                        total_receive = total_receive - %f,
                        current_balance = previous_balance + (today_receive - %f) - today_send
                    WHERE id = %d
                ", $transaction->amount, $transaction->amount, $transaction->amount, $transaction->party_id));
            }
            
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
    
    public function ajax_get_party_balance() {
        check_ajax_referer('mtp_nonce', 'nonce');
        
        if (!$this->can_user_access()) {
            wp_send_json_error('Access denied');
            return;
        }
        
        global $wpdb;
        $parties_table = $wpdb->prefix . 'mtp_parties';
        
        $party_id = intval($_POST['party_id']);
        
        $party = $wpdb->get_row($wpdb->prepare(
            "SELECT current_balance, party_name FROM $parties_table WHERE id = %d",
            $party_id
        ));
        
        if ($party) {
            wp_send_json_success(array(
                'balance' => $party->current_balance,
                'party_name' => $party->party_name
            ));
        } else {
            wp_send_json_error('Party not found');
        }
    }
    
    public function ajax_export_parties() {
        check_ajax_referer('mtp_nonce', 'nonce');
        
        if (!$this->can_user_access()) {
            wp_die('Access denied');
        }
        
        global $wpdb;
        $parties_table = $wpdb->prefix . 'mtp_parties';
        
        $parties = $wpdb->get_results("
            SELECT party_name, contact_number, email, previous_balance, current_balance, 
                   today_send, today_receive, total_send, total_receive, status, created_date
            FROM $parties_table 
            WHERE status = 'active'
            ORDER BY party_name
        ");
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="parties_export_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            'Party Name', 'Contact Number', 'Email', 'Previous Balance', 'Current Balance',
            'Today Send', 'Today Receive', 'Total Send', 'Total Receive', 'Status', 'Created Date'
        ));
        
        // CSV data
        foreach ($parties as $party) {
            fputcsv($output, array(
                $party->party_name,
                $party->contact_number,
                $party->email,
                number_format($party->previous_balance, 2),
                number_format($party->current_balance, 2),
                number_format($party->today_send, 2),
                number_format($party->today_receive, 2),
                number_format($party->total_send, 2),
                number_format($party->total_receive, 2),
                $party->status,
                $party->created_date
            ));
        }
        
        fclose($output);
        exit;
    }
    
    public function ajax_generate_report() {
        check_ajax_referer('mtp_nonce', 'nonce');
        
        if (!$this->can_user_access()) {
            wp_send_json_error('Access denied');
            return;
        }
        
        $report_type = sanitize_text_field($_POST['report_type']);
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        
        try {
            switch ($report_type) {
                case 'summary':
                    $report_data = $this->generate_summary_report($date_from, $date_to);
                    break;
                case 'party_wise':
                    $report_data = $this->generate_party_wise_report($date_from, $date_to);
                    break;
                case 'daily':
                    $report_data = $this->generate_daily_report($date_from, $date_to);
                    break;
                default:
                    wp_send_json_error('Invalid report type');
                    return;
            }
            
            ob_start();
            include MTP_PLUGIN_PATH . 'templates/report-output.php';
            $html = ob_get_clean();
            
            wp_send_json_success($html);
            
        } catch (Exception $e) {
            wp_send_json_error('Failed to generate report: ' . $e->getMessage());
        }
    }
    
    public function ajax_export_report() {
        check_ajax_referer('mtp_nonce', 'nonce');
        
        if (!$this->can_user_access()) {
            wp_die('Access denied');
        }
        
        $report_type = sanitize_text_field($_GET['report_type']);
        $date_from = sanitize_text_field($_GET['date_from']);
        $date_to = sanitize_text_field($_GET['date_to']);
        
        try {
            $report_data = $this->generate_summary_report($date_from, $date_to);
            
            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="report_' . $date_from . '_to_' . $date_to . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($output, array(
                'Party Name', 'Current Balance', 'Period Sales', 'Period Received', 'Transaction Count'
            ));
            
            // CSV data
            if (isset($report_data->party_data)) {
                foreach ($report_data->party_data as $party) {
                    fputcsv($output, array(
                        $party->party_name,
                        number_format($party->current_balance, 2),
                        number_format($party->period_sales, 2),
                        number_format($party->period_received, 2),
                        $party->transaction_count
                    ));
                }
            }
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            wp_die('Failed to export report: ' . $e->getMessage());
        }
    }
}

// Initialize the plugin
new MoneyTransferPortal();

// Helper Functions


// Helper function to check if current page is login page
function is_login_page() {
    return in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'));
}

function mtp_format_currency($amount) {
    return '₹' . number_format($amount, 2);
}

function mtp_get_party_balance($party_id) {
    global $wpdb;
    $parties_table = $wpdb->prefix . 'mtp_parties';
    return $wpdb->get_var($wpdb->prepare("SELECT current_balance FROM $parties_table WHERE id = %d", $party_id));
}

function mtp_get_daily_summary($date) {
    global $wpdb;
    $transactions_table = $wpdb->prefix . 'mtp_transactions';
    
    $summary = $wpdb->get_row($wpdb->prepare("
        SELECT 
            SUM(CASE WHEN transaction_type = 'send' THEN amount ELSE 0 END) as total_sales,
            SUM(CASE WHEN transaction_type = 'receive' THEN amount ELSE 0 END) as total_received,
            COUNT(*) as total_transactions
        FROM $transactions_table 
        WHERE transaction_date = %s
    ", $date));
    
    return $summary;
}

function mtp_log_activity($action, $details = '', $party_id = null) {
    global $wpdb;
    
    // Simple activity logging - you can expand this
    error_log("MTP Activity: $action - $details - Party ID: $party_id - User: " . get_current_user_id());
}

function debug_display($data, $label = '', $return = false) {
    
    // Add label if provided
    if (!empty($label)) {
        echo "<strong>$label:</strong><br>";
    }
    
    // Check data type and format accordingly
    if (is_array($data) || is_object($data)) {
        echo  '<pre style="background: #f4f4f4; padding: 10px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto;">';
        print_r($data);
        echo '</pre>';
        
    }
    else{
        echo $data;
    }
}
function isZero($value) {
    return floatval($value) == 0;
}


// Put this in your theme's functions.php or in a custom plugin

add_action('admin_init', function() {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';

    // If user opened wp-admin root without specifying a page
    if ( preg_match('#/wp-admin/?$#', $request_uri) ) {
        wp_redirect( admin_url( 'admin.php?page=money-transfer-portal' ) );
        exit;
    }
});
