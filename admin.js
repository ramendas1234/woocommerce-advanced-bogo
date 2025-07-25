document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.remove-bogo-rule').forEach(button => {
        button.addEventListener('click', function () {
            if (confirm('Are you sure you want to remove this rule?')) {
                const row = this.closest('.bogo-rule-row');
                if (row) row.remove();
            }
        });
    });
});
