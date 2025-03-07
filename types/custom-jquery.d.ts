// Custom jQuery type definitions for our specific needs
interface JQuery<TElement = HTMLElement> {
    // Removing duplicate declarations that exist in @types/jquery
    // Just include methods that we use and aren't covered by the official typings

    // Custom methods or overrides can go here
}

interface JQueryStatic {
    // Add call signatures to allow jQuery() function calls
    (selector: string | Element | Document | Array<Element> | JQuery | Function): JQuery;
    (readyCallback: (jq: JQueryStatic) => void): JQuery;

    // Add only custom methods or overrides
    // No need to redeclare methods already in @types/jquery
}

// No need to redeclare these globals - they're already in @types/jquery
// declare const jQuery: JQueryStatic;
// declare const $: JQueryStatic;
