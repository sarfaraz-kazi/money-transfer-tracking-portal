# Money Transfer Portal - WordPress Plugin

A simple and straightforward WordPress plugin for managing money transfer business operations, designed to replace Excel-based party and transaction management systems.

## 🌟 Features

### Core Modules
- **Dashboard** - Overview of business statistics and recent transactions
- **Parties Management** - Add, edit, delete parties with balance tracking
- **Transactions** - Record sales and received transactions with automatic balance updates
- **Reports** - Generate business reports with date ranges and export capabilities

### Key Functionality
- **Party Balance Tracking** - Automatic calculation of current balances
- **Transaction Management** - Simple sale/received transaction types
- **Real-time Updates** - Balances update automatically with each transaction
- **Search & Filter** - Quick search across parties and transactions
- **Report Generation** - Party-wise and summary reports with CSV export
- **Responsive Design** - Works on desktop, tablet, and mobile devices

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## 🚀 Installation

### Step 1: Create Plugin Structure
```
wp-content/plugins/money-transfer-portal/
├── money-transfer-portal.php    ✅ Main plugin with CRUD operations
├── README.md                    ✅ Complete documentation
├── assets/
│   ├── admin.css                ✅ Clean, responsive styling
│   └── admin.js                 ✅ Simple JavaScript functionality
└── templates/
    ├── dashboard.php            ✅ Business overview
    ├── parties.php              ✅ Party management
    ├── transactions.php         ✅ Transaction management
    └── reports.php              ✅ Reporting system
```

### Step 2: Upload Files
1. Create the folder structure above in your WordPress installation
2. Copy the provided code into each respective file
3. Upload all files maintaining the directory structure

### Step 3: Activate Plugin
1. Go to WordPress Admin → Plugins
2. Find "Money Transfer Portal"
3. Click "Activate"

### Step 4: Start Using
1. Navigate to "Money Transfer" in your admin menu
2. Add your first parties
3. Start creating transactions
4. Generate reports as needed

## 💡 Usage Guide

### Adding Parties
1. Go to **Money Transfer → Parties**
2. Click "Add New Party"
3. Fill in party details:
   - Party Name (required)
   - Contact Number
   - Email
   - Address
   - Opening Balance
4. Save the party

### Creating Transactions
1. Go to **Money Transfer → Transactions**
2. Click "Add New Transaction"
3. Select party and transaction type:
   - **Sale**: Money going out (reduces party balance)
   - **Received**: Money coming in (increases party balance)
4. Enter amount and optional details
5. Save transaction

### Generating Reports
1. Go to **Money Transfer → Reports**
2. Select date range using:
   - Custom date picker
   - Quick buttons (Today, This Week, This Month)
3. Click "Generate Report"
4. View party-wise summary and totals
5. Export to CSV or print if needed

## 🎯 How It Works

### Balance Calculation
- **Opening Balance**: Initial amount for each party
- **Current Balance**: Opening balance ± all transactions
- **Sales**: Total amount of "sale" transactions (money out)
- **Received**: Total amount of "received" transactions (money in)

### Transaction Types
- **Sale Transaction**: Records money sent out, decreases party balance
- **Received Transaction**: Records money received, increases party balance

### Database Structure
The plugin creates two main tables:
- `wp_mtp_parties` - Stores party information and balances
- `wp_mtp_transactions` - Stores all transaction records

## 🔧 Database Schema

### Parties Table
```sql
- id (Primary Key)
- party_name (Unique)
- contact_number
- email
- address
- opening_balance
- current_balance (Auto-calculated)
- total_sales (Auto-calculated)
- total_received (Auto-calculated)
- status (active/inactive)
- created_date
```

### Transactions Table
```sql
- id (Primary Key)
- reference_number (Auto-generated, Unique)
- party_id (Foreign Key)
- transaction_type (sale/received)
- amount
- description
- sender_name
- receiver_name
- transaction_date
```

## 🎨 Interface Overview

### Dashboard
- Business statistics cards
- Recent transactions table
- Quick action buttons

### Parties Management
- Complete parties list with balances
- Add/Edit/Delete party functionality
- Search parties by name or contact
- Balance totals at bottom

### Transactions
- All transactions with pagination
- Add new transactions with party selection
- Edit transaction details (sender/receiver/description)
- Delete transactions (reverses balance changes)

### Reports
- Date range selection with quick buttons
- Summary statistics cards
- Party-wise detailed breakdown
- Net position calculation
- CSV export and print functionality

## 🔒 Security Features

- **CSRF Protection**: All AJAX requests use WordPress nonces
- **Data Sanitization**: All inputs are properly sanitized
- **SQL Injection Prevention**: Uses WordPress database methods
- **User Capability Checks**: Requires 'manage_options' capability

## 📱 Browser Support

- ✅ Chrome 70+
- ✅ Firefox 65+
- ✅ Safari 12+
- ✅ Edge 79+
- ✅ Mobile browsers

## 🛠️ Customization

### Adding Fields
To add new fields to parties or transactions:
1. Update the database schema in the `create_tables()` method
2. Add form fields in the respective template files
3. Update AJAX handlers to process new fields

### Styling
- Modify `assets/admin.css` for visual changes
- Uses WordPress admin styles for consistency
- Responsive grid system for mobile compatibility

### Functionality
- Add new AJAX handlers in the main plugin file
- Extend JavaScript in `assets/admin.js`
- Create new template files for additional pages

## 📊 Reporting Features

### Summary Report
- Total transactions count
- Total sales amount
- Total received amount  
- Active parties count
- Net position (received - sales)

### Party-wise Report
- Current balance for each party
- Period-specific sales and received amounts
- Transaction count per party
- Sortable and searchable data

### Export Options
- **CSV Export**: Download party-wise data
- **Print**: Print-friendly report format
- **Date Filtering**: Custom date ranges

## 🔄 Data Management

### Backup
- Export party data via reports
- Database backup recommended before major updates
- Transaction history preserved for audit trail

### Migration from Excel
1. Export your Excel data to CSV format
2. Use the party management interface to add parties manually
3. Import transaction history as individual transactions
4. Verify balances match your Excel totals

## 📈 Best Practices

### Daily Operations
1. Check dashboard for overview
2. Add new transactions promptly
3. Verify party balances regularly
4. Generate daily/weekly reports

### Data Entry
1. Use consistent party naming
2. Include sender/receiver details for sales
3. Add descriptions for transaction clarity
4. Double-check amounts before saving

### Maintenance
1. Regular database backups
2. Keep WordPress and plugin updated
3. Monitor for any balance discrepancies
4. Archive old transaction data periodically

## 🆘 Troubleshooting

### Common Issues

**Transactions not updating balances**
- Check if transaction was saved successfully
- Verify party ID is correct
- Look for database errors in WordPress debug log

**Cannot add parties**
- Ensure party name is unique
- Check for special characters in names
- Verify database permissions

**Reports showing incorrect data**
- Check date range selection
- Verify transaction dates are correct
- Ensure all transactions have valid party associations

## 💬 Support

For support:
1. Check this documentation first
2. Enable WordPress debug mode to see error details
3. Verify database table structure matches plugin requirements
4. Contact your WordPress developer for customizations

## 📄 License

This plugin is licensed under GPL v2 or later.

---

**Simple. Reliable. Effective.**

Perfect for small to medium money transfer businesses looking to upgrade from Excel-based systems to a professional web-based solution.