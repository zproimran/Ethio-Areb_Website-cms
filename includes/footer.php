<?php
// ethioareb/includes/footer.php - CMS Footer
?>
<script>
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
});
</script>
</body>
</html>