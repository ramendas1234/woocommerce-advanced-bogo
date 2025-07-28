document.addEventListener('DOMContentLoaded', function () {
    let ruleIndex = 0;

    // Initialize rule counter based on existing rows
    function initializeRuleIndex() {
        const existingRows = document.querySelectorAll('.bogo-rule-row');
        ruleIndex = existingRows.length;
        console.log('Initialized with', ruleIndex, 'existing rows');
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
        console.log('addEmptyRule called');
        const tbody = document.getElementById('bogo-rules-tbody');
        
        if (!tbody) {
            console.error('tbody not found');
            return;
        }

        console.log('tbody found, creating new row');
        
        // Get existing row as template (much simpler approach)
        const existingRow = document.querySelector('.bogo-rule-row');
        if (!existingRow) {
            console.error('No existing row found to clone');
            return;
        }

        console.log('Cloning existing row');
        const newRow = existingRow.cloneNode(true);
        
        // Clear all values
        const inputs = newRow.querySelectorAll('input');
        inputs.forEach(input => {
            if (input.type === 'number' && input.name.includes('get_qty')) {
                input.value = '1';
            } else {
                input.value = '';
            }
        });
        
        const selects = newRow.querySelectorAll('select');
        selects.forEach(select => {
            select.selectedIndex = 0;
        });
        
        // Update all name attributes with new index
        const allInputs = newRow.querySelectorAll('input, select');
        allInputs.forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                const newName = name.replace(/\[\d+\]/, `[${ruleIndex}]`);
                input.setAttribute('name', newName);
                console.log('Updated name from', name, 'to', newName);
            }
        });
        
        newRow.setAttribute('data-index', ruleIndex);
        
        // Append to tbody
        tbody.appendChild(newRow);
        console.log('Row appended to tbody');
        
        // Add event listeners to the new row's remove button
        const removeButton = newRow.querySelector('.remove-bogo-rule');
        if (removeButton) {
            addButtonHoverEffects(removeButton);
            addRemoveHandler(removeButton);
            console.log('Event listeners added to remove button');
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
        
        console.log('New rule added successfully with index:', ruleIndex - 1);
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
        console.log('Add button found, attaching event listener');
        addButton.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Add button clicked, current ruleIndex:', ruleIndex);
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
    } else {
        console.error('Add button not found!');
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
