// ethioareb/assets/js/ethioareb.js - CMS JavaScript

$(document).ready(function() {
    
    // Mobile menu toggle
    $('#menuToggle').click(function() {
        $('#sidebar').toggleClass('open');
    });
    
    // Auto-hide alerts
    $('.alert').delay(5000).fadeOut('slow');
    
    // Confirm delete
    $('.delete-btn').click(function(e) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Image preview
    $('input[type="file"][accept*="image"]').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = $(this).closest('.file-input-wrapper').find('.image-preview');
                if (preview.length) {
                    preview.attr('src', e.target.result).show();
                }
            }.bind(this);
            reader.readAsDataURL(file);
        }
    });
    
    // Modal functions
    window.openModal = function(id) {
        $('#' + id).fadeIn(200);
        $('body').css('overflow', 'hidden');
    };
    
    window.closeModal = function(id) {
        $('#' + id).fadeOut(200);
        $('body').css('overflow', 'auto');
    };
    
    // Close modal on overlay click
    $('.modal-overlay').click(function(e) {
        if ($(e.target).hasClass('modal-overlay')) {
            $(this).fadeOut(200);
            $('body').css('overflow', 'auto');
        }
    });
    
    // Search filter
    $('.search-input').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        const target = $(this).data('target');
        $(target + ' tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    // Bulk actions
    $('.select-all').change(function() {
        $('.select-item').prop('checked', $(this).prop('checked'));
    });
    
    // Date range picker (simple)
    $('.date-range').on('change', function() {
        const start = $('.date-start').val();
        const end = $('.date-end').val();
        if (start && end) {
            window.location.href = window.location.pathname + '?start=' + start + '&end=' + end;
        }
    });
    
    // Export functionality
    $('.export-btn').click(function() {
        const format = $(this).data('format');
        const table = $(this).data('table');
        window.location.href = 'export.php?format=' + format + '&table=' + table;
    });
    
    // Clipboard copy
    $('.copy-btn').click(function() {
        const text = $(this).data('copy');
        navigator.clipboard.writeText(text).then(function() {
            $(this).html('<i class="fas fa-check text-green-500"></i>');
            setTimeout(function() {
                $(this).html('<i class="fas fa-copy"></i>');
            }.bind(this), 2000);
        }.bind(this));
    });
    
    console.log('Ethio Areb CMS loaded successfully!');
});