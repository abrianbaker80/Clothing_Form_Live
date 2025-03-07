/**
 * WordPress Localized Script Variables
 * Declaration file for variables injected by WordPress wp_localize_script()
 */

interface CategoryItem {
  name: string;
  subcategories?: Record<string, CategoryItem>;
}

interface SizeData {
  default: string[];
  [key: string]: string[] | Record<string, string[]>;
}

interface PcfFormOptions {
  ajax_url: string;
  nonce: string;
  plugin_url: string;
  debug: boolean;
  categories: Record<string, CategoryItem>;
  sizes: Record<string, SizeData | string[]>;
}

// Declare the global variable for WordPress localized script data
declare const pcfFormOptions: PcfFormOptions;

// Remove the duplicate jQuery declarations as they're already in jquery/index.d.ts
// jQuery declaration removed
// JQuery interface removed
// JQueryStatic interface removed
