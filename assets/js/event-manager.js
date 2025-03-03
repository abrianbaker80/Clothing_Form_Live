/**
 * Event Management System
 * 
 * Centralizes event handling to prevent duplicate handlers
 * and provides a clean interface for pub/sub pattern
 */
(function(global) {
    'use strict';
    
    // Create a singleton event manager
    const EventManager = {
        // Store all event handlers
        handlers: {},
        
        // One-time initialization handlers
        initializedComponents: new Set(),
        
        /**
         * Subscribe to an event
         * 
         * @param {string} eventName - The name of the event to listen for
         * @param {function} handler - The function to execute when the event fires
         * @param {string|null} componentId - Optional identifier for the component (for one-time initialization)
         * @return {object} Subscription object with unsubscribe method
         */
        subscribe: function(eventName, handler, componentId = null) {
            // If componentId is provided, check if this component has already been initialized
            if (componentId && this.initializedComponents.has(componentId)) {
                return { unsubscribe: function() {} }; // Return dummy unsubscribe
            }
            
            // Store the handler
            if (!this.handlers[eventName]) {
                this.handlers[eventName] = [];
            }
            this.handlers[eventName].push(handler);
            
            // If componentId is provided, mark it as initialized
            if (componentId) {
                this.initializedComponents.add(componentId);
            }
            
            // Return subscription object
            return {
                unsubscribe: () => {
                    this.handlers[eventName] = this.handlers[eventName].filter(h => h !== handler);
                }
            };
        },
        
        /**
         * Publish an event with data
         * 
         * @param {string} eventName - The name of the event to publish
         * @param {any} data - Optional data to pass to handlers
         */
        publish: function(eventName, data = null) {
            if (!this.handlers[eventName]) {
                return;
            }
            
            this.handlers[eventName].forEach(handler => {
                try {
                    handler(data);
                } catch (e) {
                    console.error(`Error in handler for event ${eventName}:`, e);
                }
            });
        },
        
        /**
         * Clear all handlers for an event
         * 
         * @param {string} eventName - The name of the event to clear
         */
        clear: function(eventName) {
            if (eventName) {
                this.handlers[eventName] = [];
            } else {
                this.handlers = {};
            }
        },
        
        /**
         * Check if a component has been initialized
         * 
         * @param {string} componentId - The component identifier
         * @return {boolean} True if component is initialized
         */
        isInitialized: function(componentId) {
            return this.initializedComponents.has(componentId);
        },
        
        /**
         * Mark a component as initialized
         * 
         * @param {string} componentId - The component identifier
         */
        markInitialized: function(componentId) {
            this.initializedComponents.add(componentId);
        },
        
        /**
         * Reset initialization status for all components (useful for testing)
         */
        resetInitialization: function() {
            this.initializedComponents.clear();
        }
    };
    
    // Make the event manager available globally
    global.PCF_EventManager = EventManager;
    
    // If jQuery is available, add a jQuery plugin
    if (typeof jQuery !== 'undefined') {
        jQuery.fn.pcfOn = function(eventName, handler, componentId = null) {
            const subscription = EventManager.subscribe(eventName, handler, componentId);
            return this;
        };
        
        jQuery.fn.pcfTrigger = function(eventName, data = null) {
            EventManager.publish(eventName, data);
            return this;
        };
    }
    
})(typeof window !== 'undefined' ? window : this);
