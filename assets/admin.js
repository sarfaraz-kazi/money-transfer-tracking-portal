// assets/admin.js - Complete Version

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
            const originalText = button.data('original-text') || button.html();
            button.data('original-text', originalText);
            button.html('<span class="dashicons dashicons-update mtp-spin"></span> Loading...');
        } else {
            button.prop('disabled', false).removeClass('mtp-loading-btn');
            const originalText = button.data('original-text');
            if (originalText) {
                button.html(originalText);
            }
        }
    }
    
    function formatCurrency(amount) {
        return '₹' + parseFloat(amount).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    // Modal Management
    $(document).on('click', '.mtp-modal-close', function() {
        $(this).closest('.mtp-modal').hide();
    });
    
    $(document).on('click', '.mtp-modal', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    $(document).keydown(function(e) {
        if (e.keyCode === 27) { // ESC key
            $('.mtp-modal:visible').hide();
        }
    });
    
    // PARTY MANAGEMENT - Updated for daily system
    
    // Add Party
    $(document).on('click', '#add-party-btn', function() {
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
    
    // Delete Party
    $(document).on('click', '.delete-party-btn', function() {
        const partyId = $(this).data('id');
        const partyName = $(this).closest('tr').find('.party-name').text();

        // First confirmation
        const confirmDelete = confirm(`Are you sure you want to delete "${partyName}"? This action cannot be undone.`);

        if (!confirmDelete) {
            return; // User clicked cancel
        }

        // Prompt for PIN
        const pin = prompt('Please enter your 6-digit PIN to confirm deletion:');

        // Check if user cancelled the prompt
        if (pin === null) {
            return;
        }

        // Check if PIN is exactly 6 digits
        if (!/^\d{6}$/.test(pin)) {
            alert('Invalid PIN. Please enter a 6-digit PIN.');
            return;
        }

        // Send delete request with PIN
        $.post(mtp_ajax.ajax_url, {
            action: 'mtp_delete_party',
            party_id: partyId,
            pin: pin,
            nonce: mtp_ajax.nonce
        })
            .done(function(response) {
                if (response.success) {
                    showNotification('Party deleted successfully!');
                    location.reload();
                } else {
                    showNotification(response.data || 'Error deleting party', 'error');
                }
            })
            .fail(function() {
                showNotification('An error occurred. Please try again.', 'error');
            });
    });

    $(document).on('click', '.migrate-btn', function() {
        const partyId = $(this).data('id');
        const partyName = $(this).closest('tr').find('.party-name').text();

        if (confirm(`Are you sure you want to migrate "${partyName}" data? This action cannot be undone.`)) {
            $.post(mtp_ajax.ajax_url, {
                action: 'mtp_migrate_party',
                party_id: partyId,
                nonce: mtp_ajax.nonce
            })
                .done(function(response) {
                    if (response.success) {
                        showNotification('Party migrated successfully!');
                        location.reload();
                    } else {
                        showNotification(response.data || 'Error migrating party', 'error');
                    }
                });
        }
    });


    // QUICK SEND FUNCTIONALITY
    $(document).on('click', '.send-btn', function() {
        const partyId = $(this).data('id');
        const row = $(this).closest('tr');
        const partyName = row.find('.party-name').text();
        const currentBalance = row.find('.current-balance strong').text();
        
        $('#send_party_id').val(partyId);
        $('#send-party-name').text(partyName);
        $('#send-current-balance').text(currentBalance);
        
        $('#quick-send-form')[0].reset();
        $('#send_party_id').val(partyId); // Reset removes hidden input value
        $('#quick-send-modal').show();
        
        // Focus on amount input
        setTimeout(function() {
            $('#send_amount').focus();
        }, 300);
    });
    
    $('#quick-send-form').submit(function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const amount = parseFloat($('#send_amount').val());
        
        if (!amount || amount <= 0) {
            showNotification('Please enter a valid amount', 'error');
            return;
        }
        
        showLoading(submitBtn, true);
        
        const formData = form.serialize();
        
        $.post(mtp_ajax.ajax_url, formData + '&action=mtp_quick_send&nonce=' + mtp_ajax.nonce)
            .done(function(response) {
                if (response.success) {
                    showNotification(response.data.message);
                    $('#quick-send-modal').hide();
                    
                    // Update the row in table
                    // updatePartyRow($('#send_party_id').val(), response.data.new_balance, 'send', amount);
                     location.reload();
                } else {
                    showNotification(response.data || 'Error processing send transaction', 'error');
                }
            })
            .fail(function() {
                showNotification('Network error. Please try again.', 'error');
            })
            .always(function() {
                showLoading(submitBtn, false);
            });
    });
    
    // QUICK RECEIVE FUNCTIONALITY
    $(document).on('click', '.receive-btn', function() {
        const partyId = $(this).data('id');
        const row = $(this).closest('tr');
        const partyName = row.find('.party-name').text();
        const currentBalance = row.find('.current-balance strong').text();
        
        $('#receive_party_id').val(partyId);
        $('#receive-party-name').text(partyName);
        $('#receive-current-balance').text(currentBalance);
        
        $('#quick-receive-form')[0].reset();
        $('#receive_party_id').val(partyId); // Reset removes hidden input value
        $('#quick-receive-modal').show();
        
        // Focus on amount input
        setTimeout(function() {
            $('#receive_amount').focus();
        }, 300);
    });
    
    $('#quick-receive-form').submit(function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const amount = parseFloat($('#receive_amount').val());
        
        if (!amount || amount <= 0) {
            showNotification('Please enter a valid amount', 'error');
            return;
        }
        
        showLoading(submitBtn, true);
        
        const formData = form.serialize();
        
        $.post(mtp_ajax.ajax_url, formData + '&action=mtp_quick_receive&nonce=' + mtp_ajax.nonce)
            .done(function(response) {
                if (response.success) {
                    showNotification(response.data.message);
                    $('#quick-receive-modal').hide();
                    
                    // Update the row in table
                    //updatePartyRow($('#receive_party_id').val(), response.data.new_balance, 'receive', amount);
                     location.reload();
                } else {
                    showNotification(response.data || 'Error processing receive transaction', 'error');
                }
            })
            .fail(function() {
                showNotification('Network error. Please try again.', 'error');
            })
            .always(function() {
                showLoading(submitBtn, false);
            });
    });
    /*
    // Update party row after transaction
    function updatePartyRow(partyId, newBalance, transactionType, amount) {
        const row = $(`tr[data-party-id="${partyId}"]`);
        
        // Update current balance
        const balanceCell = row.find('.current-balance');
        const balanceValue = parseFloat(newBalance);
        
        balanceCell.removeClass('mtp-positive mtp-negative mtp-zero');
        if (balanceValue > 0) {
            balanceCell.addClass('mtp-positive');
        } else if (balanceValue < 0) {
            balanceCell.addClass('mtp-negative');
        } else {
            balanceCell.addClass('mtp-zero');
        }
        
        balanceCell.find('strong').text(formatCurrency(newBalance));
        
        // Update today's send/receive
        if (transactionType === 'send') {
            const sendCell = row.find('.today-send');
            let currentSend = sendCell.text();
            if (currentSend === '—') {
                currentSend = 0;
            } else {
                currentSend = parseFloat(currentSend.replace(/[₹,]/g, '')) || 0;
            }
            const newSendAmount = currentSend + amount;
            sendCell.text(formatCurrency(newSendAmount));
        } else if (transactionType === 'receive') {
            const receiveCell = row.find('.today-receive');
            let currentReceive = receiveCell.text();
            if (currentReceive === '—') {
                currentReceive = 0;
            } else {
                currentReceive = parseFloat(currentReceive.replace(/[₹,]/g, '')) || 0;
            }
            const newReceiveAmount = currentReceive + amount;
            receiveCell.text(formatCurrency(newReceiveAmount));
        }
        
        // Highlight the updated row briefly
        row.css('background-color', '#e8f5e8');
        setTimeout(function() {
            row.css('background-color', '');
        }, 2000);
        
        // Update totals row
        updateTotalsRow();
        
        // Update total current balance in header
        updateHeaderBalance();
    }
    */
    
    function updateTotalsRow() {
        // Recalculate totals from all visible rows
        let totalSend = 0;
        let totalReceive = 0;
        let totalCurrent = 0;
        let totalPervious = 0;
        
        $('.parties-table tbody tr:visible').each(function() {
            const sendText = $(this).find('.today-send').text();
            const receiveText = $(this).find('.today-receive').text();
            const currentText = $(this).find('.current-balance strong').text();
            const perviousText = $(this).find('.previous-balance').text();
            
            if (sendText !== '—') {
                totalSend += parseFloat(sendText.replace(/[₹,]/g, '')) || 0;
            }
            if (receiveText !== '—') {
                totalReceive += parseFloat(receiveText.replace(/[₹,]/g, '')) || 0;
            }
            totalCurrent += parseFloat(currentText.replace(/[₹,]/g, '')) || 0;
            totalPervious += parseFloat(perviousText.replace(/[₹,]/g, '')) || 0;
        });
        
        // Update totals row
        const totalsRow = $('.parties-table tfoot tr');
        totalsRow.find('th:nth-child(4)').text(formatCurrency(totalSend));
        totalsRow.find('th:nth-child(5)').text(formatCurrency(totalReceive));
        
        const currentBalanceCell = totalsRow.find('th:nth-child(6)');
        currentBalanceCell.removeClass('mtp-positive mtp-negative');
        currentBalanceCell.addClass(totalCurrent >= 0 ? 'mtp-positive' : 'mtp-negative');
        currentBalanceCell.text(formatCurrency(totalCurrent));

        const prevBalanceCell = totalsRow.find('th:nth-child(3)');
        prevBalanceCell.removeClass('mtp-positive mtp-negative');
        prevBalanceCell.addClass(totalPervious >= 0 ? 'mtp-positive' : 'mtp-negative');
        prevBalanceCell.text(formatCurrency(totalPervious));
        
        // Update daily summary
        updateDailySummary(totalSend, totalReceive);
    }
    
    function updateHeaderBalance() {
        // Recalculate total current balance for header
        let totalCurrentBalance = 0;
        
        $('.parties-table tbody tr:visible').each(function() {
            const currentText = $(this).find('.current-balance strong').text();
            totalCurrentBalance += parseFloat(currentText.replace(/[₹,]/g, '')) || 0;
        });
        
        $('.mtp-total-balance strong').text('Total Current Balance: ' + formatCurrency(totalCurrentBalance));
    }
    function updateDailySummary(totalSend, totalReceive) {
        // Update the daily summary cards
        const summaryContainer = $('div:contains("Today\'s Summary")').parent();
        if (summaryContainer.length) {
            const cards = summaryContainer.find('div[style*="font-size: 24px"]');
            if (cards.length >= 3) {
                cards.eq(0).text(formatCurrency(totalSend)); // Total Send
                cards.eq(1).text(formatCurrency(totalReceive)); // Total Receive
                cards.eq(2).text(formatCurrency(totalReceive - totalSend)); // Net Amount
            }
        }
    }
    
    // Enter key shortcuts for quick transactions
    $('#send_amount').keypress(function(e) {
        if (e.which === 13) { // Enter key
            $('#quick-send-form').submit();
        }
    });
    
    $('#receive_amount').keypress(function(e) {
        if (e.which === 13) { // Enter key
            $('#quick-receive-form').submit();
        }
    });

    // TRANSACTION MANAGEMENT (keeping existing functionality)
    
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
    }
    
    // Initialize when add transaction modal opens
    $('#add-transaction-btn').click(function() {
        $('#add-transaction-modal').show();
        $('#add-transaction-form')[0].reset();
        $('#selected_party_info').hide();
        setTimeout(initializeSearchableDropdown, 100);
    });

    // Add Transaction
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
        
        if (type === 'send') {
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
        
        // Update totals after filtering
        updateTotalsRow();
        updateHeaderBalance();
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
    
    console.log('Money Transfer Portal - Complete Version Loaded');
});