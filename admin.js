document.addEventListener('DOMContentLoaded', function () {
    // Function to clear a rule row instead of removing it
    function clearRule(row) {
        // Clear all input fields
        const inputs = row.querySelectorAll('input');
        inputs.forEach(input => {
            input.value = '';
        });
        
        // Reset all select fields to their first option
        const selects = row.querySelectorAll('select');
        selects.forEach(select => {
            select.selectedIndex = 0;
        });
    }

    // Add hover effects and click handlers
    document.querySelectorAll('.remove-bogo-rule').forEach(button => {
        // Add hover effects
        button.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#dc3545';
            this.style.color = '#fff';
            this.style.transform = 'scale(1.1)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.backgroundColor = 'transparent';
            this.style.color = '#dc3545';
            this.style.transform = 'scale(1)';
        });

        // Add click handler
        button.addEventListener('click', function () {
            if (confirm('Are you sure you want to clear this rule?')) {
                const row = this.closest('.bogo-rule-row');
                if (row) {
                    clearRule(row);
                    // Add a brief highlight effect to show the rule was cleared
                    row.style.backgroundColor = '#fff3cd';
                    setTimeout(() => {
                        row.style.backgroundColor = '';
                    }, 1000);
                }
            }
        });
    });
});