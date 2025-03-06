/**
 * Custom jQuery extensions for Clothing Form
 * This file only includes project-specific extensions to the official jQuery types
 */

// Import official jQuery types
/// <reference types="jquery" />

// Only declare extensions to jQuery that aren't in the official types
interface JQuery<TElement = HTMLElement> {
    // Add only custom methods here, like:
    // customProjectMethod(): void;
}

// Extend JQueryStatic with custom methods only
interface JQueryStatic {
    // Add only custom static methods, like:
    // customStaticMethod(): void;
}
