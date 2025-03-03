<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// **Centralized "One Size" Options**
$one_size_options = ['One Size', 'N/A', 'OS'];

// **Centralized Size Type Definitions - OPTIMIZATION:**
$common_size_types = array(
    'standard_tops_women' => array(
        'Standard' => array('XS', 'S', 'M', 'L', 'XL', 'XXL'),
        'Plus Size' => array('0X', '1X', '2X', '3X', '4X', '5X'),
        'Petite' => array('PXS', 'PS', 'PM', 'PL', 'PXL'),
        'Juniors' => array('0', '1', '3', '5', '7', '9', '11', '13', '15'),
        'One Size' => $one_size_options,
    ),
    'standard_jackets_women' => array(
        'Standard' => array('XS', 'S', 'M', 'L', 'XL', 'XXL'),
        'Plus Size' => array('0X', '1X', '2X', '3X', '4X', '5X'),
        'Petite' => array('PXS', 'PS', 'PM', 'PL', 'PXL'),
        'One Size' => $one_size_options,
    ),
    'one_size_only' => array(
        'One Size' => $one_size_options,
    ),
    'standard_bottoms_women' => array(
        'Standard' => array('0', '2', '4', '6', '8', '10', '12', '14', '16', '18'),
        'Plus Size' => array('14W', '16W', '18W', '20W', '22W', '24W', '26W', '28W', '30W', '32W'),
        'Petite' => array('0P', '2P', '4P', '6P', '8P', '10P', '12P', '14P'),
        'Curvy/Trendy' => array('24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35', '36'),
        'One Size' => $one_size_options,
    ),
    'standard_dresses_women' => array(
        'Standard' => array('XS', 'S', 'M', 'L', 'XL', 'XXL'),
        'Plus Size' => array('0X', '1X', '2X', '3X', '4X', '5X'),
        'Petite' => array('PXS', 'PS', 'PM', 'PL', 'PXL'),
        'One Size' => $one_size_options,
    ),
    'standard_suits_women' => array(
        'Standard' => array('0', '2', '4', '6', '8', '10', '12', '14', '16', '18'),
        'Plus Size' => array('14W', '16W', '18W', '20W', '22W', '24W', '26W'),
        'Petite' => array('0P', '2P', '4P', '6P', '8P', '10P', '12P', '14P'),
        'One Size' => $one_size_options,
    ),
    'standard_activewear_bras_intimates_women' => array(
        'Standard' => array('XS', 'S', 'M', 'L', 'XL', 'XXL'),
        'Plus Size' => array('0X', '1X', '2X', '3X', '4X', '5X'),
        'Bra Size' => array('32A', '32B', '32C', '32D', '34A', '34B', '34C', '34D', '36A', '36B', '36C', '36D', '38A', '38B', '38C', '38D', '40A', '40B', '40C', '40D'),
        'One Size' => $one_size_options,
    ),
    'standard_shoes_women' => array(
        "US Women's" => array('5', '5.5', '6', '6.5', '7', '7.5', '8', '8.5', '9', '9.5', '10', '10.5', '11', '11.5', '12'),
        "UK Women's" => array('3', '3.5', '4', '4.5', '5', '5.5', '6', '6.5', '7', '7.5', '8', '8.5', '9', '9.5'),
        'EU' => array('35', '36', '37', '38', '39', '40', '41', '42', '43', '44'),
        'Width' => array('Narrow', 'Medium', 'Wide', 'Extra Wide'),
        'One Size' => $one_size_options,
    ),
    'standard_accessories_women' => array(
        'One Size' => $one_size_options,
        'S/M/L' => array('S', 'M', 'L'),
        'Ring Size' => array('5', '5.5', '6', '6.5', '7', '7.5', '8', '8.5', '9', '9.5', '10'),
        'Belt Size' => array('XS (26-28")', 'S (29-31")', 'M (32-34")', 'L (35-37")', 'XL (38-40")', 'XXL (41-43")'),
        'Handbag/Purse Size' => array('Mini', 'Small', 'Medium', 'Large', 'Oversized'),
    ),
    'standard_tops_men' => array(
        'Standard' => array('S', 'M', 'L', 'XL', 'XXL', 'XXXL'),
        'Big & Tall' => array('LT', 'XLT', 'XXLT', 'XXXLT', '2XB', '3XB', '4XB', '5XB'),
        'Neck Size' => array('14', '14.5', '15', '15.5', '16', '16.5', '17', '17.5', '18', '18.5', '19', '19.5'),
        'Sleeve Length' => array('32/33', '34/35', '36/37'),
        'One Size' => $one_size_options,
    ),
    'standard_bottoms_men' => array(
        'Waist & Inseam' => array('30x30', '30x32', '32x30', '32x32', '32x34', '34x30', '34x32', '34x34', '36x30', '36x32', '36x34', '38x30', '38x32', '38x34'),
        'Standard' => array('28', '30', '32', '34', '36', '38', '40', '42', '44'),
        'Big & Tall' => array('46', '48', '50', '52', '54', '56', '58', '60'),
        'One Size' => $one_size_options,
    ),
    'standard_outerwear_men' => array(
        'Standard' => array('S', 'M', 'L', 'XL', 'XXL', 'XXXL'),
        'Big & Tall' => array('LT', 'XLT', 'XXLT', 'XXXLT', '2XB', '3XB', '4XB', '5XB'),
        'One Size' => $one_size_options,
    ),
    'standard_suits_men' => array(
        'Suit Size' => array('36S', '36R', '36L', '38S', '38R', '38L', '40S', '40R', '40L', '42S', '42R', '42L', '44S', '44R', '44L', '46S', '46R', '46L', '48R', '48L', '50R', '50L', '52R', '52L'),
        'Jacket Size' => array('36', '38', '40', '42', '44', '46', '48', '50', '52'),
        'Pant Size' => array('30', '32', '34', '36', '38', '40', '42', '44', '46'),
        'One Size' => $one_size_options,
    ),
    'standard_activewear_men' => array(
        'Standard' => array('S', 'M', 'L', 'XL', 'XXL', 'XXXL'),
        'One Size' => $one_size_options,
    ),
    'standard_underwear_loungewear_men' => array(
        'Standard' => array('S', 'M', 'L', 'XL', 'XXL', 'XXXL'),
        'One Size' => $one_size_options,
    ),
    'standard_shoes_men' => array(
        "US Men's" => array('7', '7.5', '8', '8.5', '9', '9.5', '10', '10.5', '11', '11.5', '12', '13', '14', '15'),
        "UK Men's" => array('6', '6.5', '7', '7.5', '8', '8.5', '9', '9.5', '10', '10.5', '11', '12', '13', '14'),
        'EU' => array('40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '50'),
        'Width' => array('Narrow', 'Medium', 'Wide', 'Extra Wide'),
        'One Size' => $one_size_options,
    ),
    'standard_accessories_men' => array(
        'One Size' => $one_size_options,
        'Belt Size' => array('30', '32', '34', '36', '38', '40', '42', '44', '46', '48'),
        'Hat Size' => array('S', 'M', 'L', 'XL'),
    ),
);

$clothing_categories_hierarchical = array(
    'womens' => array(
        'label' => 'Women\'s Clothing',
        'categories' => array(
            'Tops' => array(
                'label' => 'Tops',
                'size_types' => $common_size_types['standard_tops_women'],
            ),
            'Jackets & Coats' => array(
                'label' => 'Jackets & Coats',
                'categories' => array(
                    'Blazers & Suit Jackets' => array(
                        'label' => 'Blazers & Suit Jackets',
                    ),
                    'Bomber Jackets' => array(
                        'label' => 'Bomber Jackets',
                    ),
                    'Capes' => array(
                        'label' => 'Capes',
                        'size_types' => $common_size_types['one_size_only'],
                    ),
                    'Jean Jackets' => array(
                        'label' => 'Jean Jackets',
                    ),
                    'Leather Jackets' => array(
                        'label' => 'Leather Jackets',
                    ),
                    'Pea Coats' => array(
                        'label' => 'Pea Coats',
                    ),
                    'Puffers' => array(
                        'label' => 'Puffers',
                    ),
                    'Ski & Snow Jackets' => array(
                        'label' => 'Ski & Snow Jackets',
                    ),
                    'Teddy Jackets' => array(
                        'label' => 'Teddy Jackets',
                    ),
                    'Trench Coats' => array(
                        'label' => 'Trench Coats',
                    ),
                    'Utility Jackets' => array(
                        'label' => 'Utility Jackets',
                    ),
                ),
                'size_types' => $common_size_types['standard_jackets_women'],
            ),
            'Bottoms' => array(
                'label' => 'Bottoms',
                'size_types' => $common_size_types['standard_bottoms_women'],
            ),
            'Dresses' => array(
                'label' => 'Dresses',
                'size_types' => $common_size_types['standard_dresses_women'],
            ),
            'Suits & Sets' => array(
                'label' => 'Suits & Sets',
                'size_types' => $common_size_types['standard_suits_women'],
            ),
            'Activewear' => array(
                'label' => 'Activewear',
                'size_types' => $common_size_types['standard_activewear_bras_intimates_women'],
            ),
            'Bras & Intimates' => array(
                'label' => 'Bras & Intimates',
                'size_types' => $common_size_types['standard_activewear_bras_intimates_women'],
            ),
            'Shoes' => array(
                'label' => 'Shoes',
                'size_types' => $common_size_types['standard_shoes_women'],
            ),
            'Accessories' => array(
                'label' => 'Accessories',
                'size_types' => $common_size_types['standard_accessories_women'],
            ),
        ),
        'size_types' => $common_size_types['standard_tops_women'],
    ),
    'mens' => array(
        'label' => 'Men\'s Clothing',
        'categories' => array(
            'Tops' => array(
                'label' => 'Tops',
                'size_types' => $common_size_types['standard_tops_men'],
            ),
            'Bottoms' => array(
                'label' => 'Bottoms',
                'size_types' => $common_size_types['standard_bottoms_men'],
            ),
            'Outerwear' => array(
                'label' => 'Outerwear',
                'size_types' => $common_size_types['standard_outerwear_men'],
            ),
            'Suits & Sets' => array(
                'label' => 'Suits & Sets',
                'size_types' => $common_size_types['standard_suits_men'],
            ),
            'Activewear' => array(
                'label' => 'Activewear',
                'size_types' => $common_size_types['standard_activewear_men'],
            ),
            'Underwear & Loungewear' => array(
                'label' => 'Underwear & Loungewear',
                'size_types' => $common_size_types['standard_underwear_loungewear_men'],
            ),
            'Shoes' => array(
                'label' => 'Shoes',
                'size_types' => $common_size_types['standard_shoes_men'],
            ),
            'Accessories' => array(
                'label' => 'Accessories',
                'size_types' => $common_size_types['standard_accessories_men'],
            ),
        ),
        'size_types' => $common_size_types['standard_tops_men'],
    ),
);

// Make this structure available globally
$GLOBALS['clothing_categories_hierarchical'] = $clothing_categories_hierarchical;

// Return the hierarchical data structure
return $clothing_categories_hierarchical;