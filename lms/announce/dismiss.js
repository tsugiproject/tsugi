/**
 * Generic dismiss/undismiss functionality
 * 
 * Usage:
 *   dismissToggle.init(dismissUrl, options);
 * 
 * Options:
 *   - selector: CSS selector for dismiss triggers (default: '[data-dismiss-toggle]')
 *   - itemSelector: CSS selector or function to find parent item (default: 'closest' to parent with data attribute)
 *   - idAttribute: data attribute name for the item ID (default: 'data-dismiss-id')
 *   - dismissedClass: CSS class to add when dismissed (default: 'dismissed')
 *   - formDataBuilder: function(id, dismiss) returning FormData or object (default: {id: id, dismiss: dismiss})
 *   - onBeforeToggle: callback(item, trigger, currentState, newState) - return false to cancel
 *   - onSuccess: callback(item, trigger, isDismissed, response)
 *   - onError: callback(error, item, trigger)
 *   - updateUI: callback(item, trigger, isDismissed) - custom UI update logic
 */

var dismissToggle = (function() {
    'use strict';
    
    var dismissUrl = null;
    var options = {};
    var defaults = {
        selector: '[data-dismiss-toggle]',
        idAttribute: 'data-dismiss-id',
        dismissedClass: 'dismissed',
        formDataBuilder: null,
        onBeforeToggle: null,
        onSuccess: null,
        onError: null,
        updateUI: null
    };
    
    /**
     * Initialize the dismiss functionality
     * @param {string} url - The URL to the dismiss endpoint
     * @param {object} opts - Configuration options
     */
    function init(url, opts) {
        dismissUrl = url;
        options = Object.assign({}, defaults, opts || {});
        
        // Attach click handlers to all dismiss triggers
        var triggers = document.querySelectorAll(options.selector);
        triggers.forEach(function(trigger) {
            trigger.addEventListener('click', handleClick);
        });
    }
    
    /**
     * Handle click on dismiss trigger
     */
    function handleClick(e) {
        e.preventDefault();
        var trigger = this;
        var itemId = trigger.getAttribute(options.idAttribute);
        if (!itemId) {
            handleError('Missing item ID attribute: ' + options.idAttribute, null, trigger);
            return;
        }
        
        var item = findItem(trigger);
        var isDismissed = isItemDismissed(item, trigger);
        var newDismissState = !isDismissed;
        
        // Call before toggle callback
        if (options.onBeforeToggle && typeof options.onBeforeToggle === 'function') {
            if (options.onBeforeToggle(item, trigger, isDismissed, newDismissState) === false) {
                return; // Cancelled
            }
        }
        
        // Disable trigger to prevent double-clicks
        trigger.style.pointerEvents = 'none';
        
        // Call the dismiss function
        toggleDismiss(itemId, newDismissState, item, trigger);
    }
    
    /**
     * Find the parent item element
     */
    function findItem(trigger) {
        if (typeof options.itemSelector === 'function') {
            return options.itemSelector(trigger);
        } else if (typeof options.itemSelector === 'string') {
            return trigger.closest(options.itemSelector);
        } else {
            // Default: find closest parent with the id attribute
            var parent = trigger.parentElement;
            while (parent && parent !== document.body) {
                if (parent.hasAttribute(options.idAttribute)) {
                    return parent;
                }
                parent = parent.parentElement;
            }
            // Fallback: return closest element with a common container class
            return trigger.closest('[class*="item"], [class*="card"], [class*="panel"]') || trigger.parentElement;
        }
    }
    
    /**
     * Check if item is currently dismissed
     */
    function isItemDismissed(item, trigger) {
        if (item && item.classList.contains(options.dismissedClass)) {
            return true;
        }
        if (trigger && trigger.classList.contains(options.dismissedClass)) {
            return true;
        }
        return false;
    }
    
    /**
     * Toggle dismiss state
     * @param {string|number} itemId - The item ID
     * @param {boolean} dismiss - True to dismiss, false to undismiss
     * @param {HTMLElement} item - The item element
     * @param {HTMLElement} trigger - The trigger element
     */
    function toggleDismiss(itemId, dismiss, item, trigger) {
        // Build form data
        var formData;
        if (options.formDataBuilder && typeof options.formDataBuilder === 'function') {
            var data = options.formDataBuilder(itemId, dismiss);
            if (data instanceof FormData) {
                formData = data;
            } else {
                formData = new FormData();
                for (var key in data) {
                    if (data.hasOwnProperty(key)) {
                        formData.append(key, data[key]);
                    }
                }
            }
        } else {
            formData = new FormData();
            formData.append('id', itemId);
            formData.append('dismiss', dismiss ? 1 : 0);
        }
        
        fetch(dismissUrl, {
            method: 'POST',
            body: formData
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.status === 'success') {
                // Update UI
                if (options.updateUI && typeof options.updateUI === 'function') {
                    options.updateUI(item, trigger, dismiss);
                } else {
                    defaultUpdateUI(item, trigger, dismiss);
                }
                
                // Call success callback
                if (options.onSuccess && typeof options.onSuccess === 'function') {
                    options.onSuccess(item, trigger, dismiss, data);
                }
            } else {
                var errorMsg = data.detail || data.error || 'Failed to update item';
                handleError(errorMsg, item, trigger);
            }
            trigger.style.pointerEvents = 'auto';
        })
        .catch(function(error) {
            handleError('Error updating item. Please try again.', item, trigger);
            trigger.style.pointerEvents = 'auto';
        });
    }
    
    /**
     * Default UI update logic
     */
    function defaultUpdateUI(item, trigger, isDismissed) {
        if (item) {
            if (isDismissed) {
                item.classList.add(options.dismissedClass);
            } else {
                item.classList.remove(options.dismissedClass);
            }
        }
        if (trigger) {
            if (isDismissed) {
                trigger.classList.add(options.dismissedClass);
                trigger.setAttribute('title', trigger.getAttribute('data-dismiss-title-undismiss') || 'Undismiss');
            } else {
                trigger.classList.remove(options.dismissedClass);
                trigger.setAttribute('title', trigger.getAttribute('data-dismiss-title') || 'Dismiss');
            }
        }
    }
    
    /**
     * Handle errors
     */
    function handleError(message, item, trigger) {
        if (options.onError && typeof options.onError === 'function') {
            options.onError(message, item, trigger);
        } else {
            alert(message);
        }
    }
    
    // Public API
    return {
        init: init,
        toggleDismiss: toggleDismiss
    };
})();
