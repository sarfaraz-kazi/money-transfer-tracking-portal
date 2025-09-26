// assets/admin.js - Simple Version

jQuery(document).ready(function($) {
    'use strict';
    
    // Utility functions
    function showNotification(message, type = 'success') {
        const notification = $(`<div class="mtp-notification mtp-notification-${type}">${message}</div>`);
        $('body').append(notification);
        
        notification.fadeIn(300);
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 4000);
    }
    
    function showLoading(button, show = true) {
        if (show) {
            button.prop('disabled', true).addClass('mtp-loading-btn');
        } else {
            button.prop('disabled', false).removeClass('mtp-loading-btn');
        }
    }
    
    // Modal Management
    $('.mtp-modal-close').click(function() {
        $(this).closest('.mtp-modal').hide();
    });
    
    $('.mtp-modal').click(function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    $(document).keydown(function(e) {
        if (e.keyCode === 27) { // ESC key
            $('.mtp-modal:visible').hide();
        }
    });
    
    // PARTY MANAGEMENT
    
    // Add Party
    $('#add-party-btn').click(function() {
        $('#add-party-modal').show();
        $('#add-party-form')[0].reset();
    });
    
    $('#add-party-form').submit(function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        showLoading(submitBtn, true);
        
        const formData = form.serialize();
        
        $.post(mtp_ajax.ajax_url, formData + '&action=mtp_add_party&nonce=' + mtp_ajax.nonce)
            .done(function(response) {
                if (response.success) {
                    showNotification('Party added successfully!');
                    $('#add-party-modal').hide();
                    location.reload();
                } else {
                    showNotification(response.data || 'Error adding party', 'error');
                }
            })
            .fail(function() {
                showNotification('Network error. Please try again.', 'error');
            })
            .always(function() {
                showLoading(submitBtn, false);
            });
    });
    
    // Edit Party
    $('.edit-party-btn').click(function() {
        const row = $(this).closest('tr');
        const partyId = $(this).data('id');
        const partyName = row.find('.party-name').text();
        const contact = row.find('.party-contact').text();
        const email = row.find('.party-email').text();
        const address = row.find('.party-address').text();
        
        $('#edit_party_id').val(partyId);
        $('#edit_party_name').val(partyName);
        $('#edit_contact_number').val(contact);
        $('#edit_email').val(email);
        $('#edit_address').val(address);
        
        $('#edit-party-modal').show();
    });
    
    $('#edit-party-form').submit(function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        showLoading(submitBtn, true);
        
        const formData = form.serialize();
        
        $.post(mtp_ajax.ajax_url, formData + '&action=mtp_edit_party&nonce=' + mtp_ajax.nonce)
            .done(function(response) {
                if (response.success) {
                    showNotification('Party updated successfully!');
                    $('#edit-party-modal').hide();
                    location.reload();
                } else {
                    showNotification(response.data || 'Error updating party', 'error');
                }
            })
            .always(function() {
                showLoading(submitBtn, false);
            });
    });
    
    // Delete Party
    $('.delete-party-btn').click(function() {
        const partyId = $(this).data('id');
        const partyName = $(this).closest('tr').find('.party-name').text();
        
        if (confirm(`Are you sure you want to delete "${partyName}"? This action cannot be undone.`)) {
            $.post(mtp_ajax.ajax_url, {
                action: 'mtp_delete_party',
                party_id: partyId,
                nonce: mtp_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    showNotification('Party deleted successfully!');
                    location.reload();
                } else {
                    showNotification(response.data || 'Error deleting party', 'error');
                }
            });
        }
    });
    
    // Searchable Party Dropdown
    function initializeSearchableDropdown() {
        const searchInput = $('#party_search');
        const hiddenInput = $('#party_id');
        const dropdown = $('#party_dropdown');
        const partyInfo = $('#selected_party_info');
        const partyBalance = $('#party_balance');
        
        // Show dropdown on focus
        searchInput.on('focus', function() {
            dropdown.show();
            filterDropdownItems('');
        });
        
        // Hide dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.mtp-searchable-select').length) {
                dropdown.hide();
            }
        });
        
        // Filter items as user types
        searchInput.on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            filterDropdownItems(searchTerm);
        });
        
        // Handle item selection
        dropdown.on('click', '.mtp-dropdown-item', function() {
            const value = $(this).data('value');
            const balance = $(this).data('balance');
            const text = $(this).find('strong').text();
            
            searchInput.val(text);
            hiddenInput.val(value);
            partyBalance.text(formatCurrency(balance));
            partyInfo.show();
            dropdown.hide();
        });
        
        // Keyboard navigation
        let selectedIndex = -1;
        searchInput.on('keydown', function(e) {
            const visibleItems = dropdown.find('.mtp-dropdown-item:visible');
            
            if (e.keyCode === 40) { // Down arrow
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, visibleItems.length - 1);
                updateSelection(visibleItems);
            } else if (e.keyCode === 38) { // Up arrow
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateSelection(visibleItems);
            } else if (e.keyCode === 13) { // Enter
                e.preventDefault();
                if (selectedIndex >= 0) {
                    visibleItems.eq(selectedIndex).click();
                }
            } else if (e.keyCode === 27) { // Escape
                dropdown.hide();
                selectedIndex = -1;
            }
        });
        
        function filterDropdownItems(searchTerm) {
            const items = dropdown.find('.mtp-dropdown-item');
            let visibleCount = 0;
            
            items.each(function() {
                const text = $(this).text().toLowerCase();
                const shouldShow = text.indexOf(searchTerm) > -1;
                $(this).toggle(shouldShow);
                if (shouldShow) visibleCount++;
            });
            
            // Show "No results" message if no items match
            dropdown.find('.no-results').remove();
            if (visibleCount === 0 && searchTerm) {
                dropdown.append('<div class="no-results mtp-dropdown-item" style="color: #666; font-style: italic;">No parties found</div>');
            }
            
            selectedIndex = -1;
        }
        
        function updateSelection(visibleItems) {
            visibleItems.removeClass('selected');
            if (selectedIndex >= 0) {
                visibleItems.eq(selectedIndex).addClass('selected');
            }
        }
        
        function formatCurrency(amount) {
            return '₹' + parseFloat(amount).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    }
    
    // Initialize when add transaction modal opens
    $('#add-transaction-btn').click(function() {
        $('#add-transaction-modal').show();
        $('#add-transaction-form')[0].reset();
        $('#selected_party_info').hide();
        setTimeout(initializeSearchableDropdown, 100);
    });

    // TRANSACTION MANAGEMENT
    
    // Add Transaction
    $('#add-transaction-btn').click(function() {
        $('#add-transaction-modal').show();
        $('#add-transaction-form')[0].reset();
    });
    
    $('#add-transaction-form').submit(function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        showLoading(submitBtn, true);
        
        const formData = form.serialize();
        
        $.post(mtp_ajax.ajax_url, formData + '&action=mtp_add_transaction&nonce=' + mtp_ajax.nonce)
            .done(function(response) {
                if (response.success) {
                    showNotification('Transaction added successfully!');
                    $('#add-transaction-modal').hide();
                    location.reload();
                } else {
                    showNotification(response.data || 'Error adding transaction', 'error');
                }
            })
            .fail(function() {
                showNotification('Network error. Please try again.', 'error');
            })
            .always(function() {
                showLoading(submitBtn, false);
            });
    });
    
    // Edit Transaction
    $('.edit-transaction-btn').click(function() {
        const row = $(this).closest('tr');
        const transactionId = $(this).data('id');
        const description = row.find('.transaction-description').text();
        const sender = row.find('.transaction-sender').text();
        const receiver = row.find('.transaction-receiver').text();
        
        $('#edit_transaction_id').val(transactionId);
        $('#edit_description').val(description);
        $('#edit_sender_name').val(sender);
        $('#edit_receiver_name').val(receiver);
        
        $('#edit-transaction-modal').show();
    });
    
    $('#edit-transaction-form').submit(function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        showLoading(submitBtn, true);
        
        const formData = form.serialize();
        
        $.post(mtp_ajax.ajax_url, formData + '&action=mtp_edit_transaction&nonce=' + mtp_ajax.nonce)
            .done(function(response) {
                if (response.success) {
                    showNotification('Transaction updated successfully!');
                    $('#edit-transaction-modal').hide();
                    location.reload();
                } else {
                    showNotification(response.data || 'Error updating transaction', 'error');
                }
            })
            .always(function() {
                showLoading(submitBtn, false);
            });
    });
    
    // Delete Transaction
    $('.delete-transaction-btn').click(function() {
        const transactionId = $(this).data('id');
        const reference = $(this).closest('tr').find('.transaction-reference').text();
        
        if (confirm(`Are you sure you want to delete transaction "${reference}"? This action cannot be undone.`)) {
            $.post(mtp_ajax.ajax_url, {
                action: 'mtp_delete_transaction',
                transaction_id: transactionId,
                nonce: mtp_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    showNotification('Transaction deleted successfully!');
                    location.reload();
                } else {
                    showNotification(response.data || 'Error deleting transaction', 'error');
                }
            });
        }
    });
    
    // Transaction Type Change
    $('#transaction_type').change(function() {
        const type = $(this).val();
        
        if (type === 'sale') {
            $('.sender-receiver-fields').show();
        } else {
            $('.sender-receiver-fields').hide();
        }
    });
    
    // REPORTS
    
    // Generate Report
    $('#generate-report-btn').click(function() {
        const dateFrom = $('#date_from').val();
        const dateTo = $('#date_to').val();
        
        if (!dateFrom || !dateTo) {
            showNotification('Please select both from and to dates', 'warning');
            return;
        }
        
        const url = window.location.pathname + window.location.search + 
            (window.location.search ? '&' : '?') + 
            'generate_report=1&date_from=' + dateFrom + '&date_to=' + dateTo;
            
        window.location.href = url;
    });
    
    // Export Report
    $('#export-report-btn').click(function() {
        const dateFrom = $('#date_from').val();
        const dateTo = $('#date_to').val();
        
        if (!dateFrom || !dateTo) {
            showNotification('Please generate a report first', 'warning');
            return;
        }
        
        // Create CSV content
        let csvContent = "data:text/csv;charset=utf-8,";
        
        // Add headers
        csvContent += "Party Name,Current Balance,Period Sales,Period Received,Transaction Count\n";
        
        // Add data rows
        $('.party-report-row').each(function() {
            const row = $(this);
            const partyName = row.find('.party-name').text();
            const balance = row.find('.party-balance').text().replace('₹', '').replace(/,/g, '');
            const sales = row.find('.party-sales').text().replace('₹', '').replace(/,/g, '');
            const received = row.find('.party-received').text().replace('₹', '').replace(/,/g, '');
            const count = row.find('.party-count').text();
            
            csvContent += `"${partyName}",${balance},${sales},${received},${count}\n`;
        });
        
        // Create download link
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "report_" + dateFrom + "_to_" + dateTo + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
    
    // Print Report
    $('#print-report-btn').click(function() {
        window.print();
    });
    
    // Search Functionality
    $('#party-search').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.parties-table tbody tr').each(function() {
            const row = $(this);
            const text = row.text().toLowerCase();
            row.toggle(text.indexOf(searchTerm) > -1);
        });
    });
    
    $('#transaction-search').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.transactions-table tbody tr').each(function() {
            const row = $(this);
            const text = row.text().toLowerCase();
            row.toggle(text.indexOf(searchTerm) > -1);
        });
    });
    
    // Quick Date Buttons
    $('.quick-date-btn').click(function() {
        const period = $(this).data('period');
        const today = new Date();
        let fromDate, toDate;
        
        switch(period) {
            case 'today':
                fromDate = toDate = today.toISOString().split('T')[0];
                break;
            case 'week':
                const weekStart = new Date(today.setDate(today.getDate() - today.getDay()));
                fromDate = weekStart.toISOString().split('T')[0];
                toDate = new Date().toISOString().split('T')[0];
                break;
            case 'month':
                fromDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                toDate = new Date().toISOString().split('T')[0];
                break;
        }
        
        $('#date_from').val(fromDate);
        $('#date_to').val(toDate);
    });
    
    console.log('Money Transfer Portal - Simple Version Loaded');
});