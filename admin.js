document.addEventListener('DOMContentLoaded', function () {
    let ruleIndex = 0;

    // Initialize rule counter based on existing rows
    function initializeRuleIndex() {
        const existingRows = document.querySelectorAll('.bogo-rule-row');
        ruleIndex = existingRows.length;
    }

    // Add hover effects to a button
    function addButtonHoverEffects(button) {
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
    }

    // Add remove functionality to a button
    function addRemoveHandler(button) {
        button.addEventListener('click', function () {
            if (confirm('Are you sure you want to remove this rule?')) {
                const row = this.closest('.bogo-rule-row');
                if (row) {
                    // Add fade out effect
                    row.style.transition = 'opacity 0.3s ease';
                    row.style.opacity = '0';
                    
                    setTimeout(() => {
                        row.remove();
                        updateRuleIndices();
                        
                        // Show message if no rules left
                        const tbody = document.getElementById('bogo-rules-tbody');
                        if (tbody.children.length === 0) {
                            addEmptyRule();
                        }
                    }, 300);
                }
            }
        });
    }

    // Update all rule indices after removal
    function updateRuleIndices() {
        const rows = document.querySelectorAll('.bogo-rule-row');
        rows.forEach((row, index) => {
            row.setAttribute('data-index', index);
            
            // Update all name attributes
            const inputs = row.querySelectorAll('input, select');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    const newName = name.replace(/\[\d+\]/, `[${index}]`);
                    input.setAttribute('name', newName);
                }
            });
        });
        
        ruleIndex = rows.length;
    }

    // Add a new empty rule
    function addEmptyRule() {
        const template = document.getElementById('bogo-rule-template');
        const tbody = document.getElementById('bogo-rules-tbody');
        
        if (template && tbody) {
            const clone = template.content.cloneNode(true);
            const row = clone.querySelector('.bogo-rule-row');
            
            // Replace __INDEX__ with actual index
            const html = row.outerHTML.replace(/__INDEX__/g, ruleIndex);
            row.outerHTML = html;
            
            tbody.appendChild(clone);
            
            // Add event listeners to the new row's remove button
            const newRow = tbody.lastElementChild;
            const removeButton = newRow.querySelector('.remove-bogo-rule');
            if (removeButton) {
                addButtonHoverEffects(removeButton);
                addRemoveHandler(removeButton);
            }
            
            ruleIndex++;
            
            // Add slide-in effect
            newRow.style.opacity = '0';
            newRow.style.transform = 'translateY(-10px)';
            newRow.style.transition = 'all 0.3s ease';
            
            setTimeout(() => {
                newRow.style.opacity = '1';
                newRow.style.transform = 'translateY(0)';
            }, 10);
        }
    }

    // Initialize existing remove buttons
    function initializeExistingButtons() {
        document.querySelectorAll('.remove-bogo-rule').forEach(button => {
            addButtonHoverEffects(button);
            addRemoveHandler(button);
        });
    }

    // Add new rule button handler
    const addButton = document.getElementById('add-bogo-rule');
    if (addButton) {
        addButton.addEventListener('click', function() {
            addEmptyRule();
            
            // Scroll to the new rule
            setTimeout(() => {
                const tbody = document.getElementById('bogo-rules-tbody');
                const lastRow = tbody.lastElementChild;
                if (lastRow) {
                    lastRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 100);
        });
    }

    // Initialize everything
    initializeRuleIndex();
    initializeExistingButtons();

    // Form validation before submit
    const form = document.getElementById('bogo-rules-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const rows = document.querySelectorAll('.bogo-rule-row');
            let hasValidRule = false;
            
            rows.forEach(row => {
                const buyProduct = row.querySelector('[name*="[buy_product]"]').value;
                const getProduct = row.querySelector('[name*="[get_product]"]').value;
                const buyQty = row.querySelector('[name*="[buy_qty]"]').value;
                const discount = row.querySelector('[name*="[discount]"]').value;
                
                if (buyProduct && getProduct && buyQty && discount) {
                    hasValidRule = true;
                }
            });
            
            if (!hasValidRule) {
                e.preventDefault();
                alert('Please add at least one complete BOGO rule before saving.');
                return false;
            }
        });
    }
});
