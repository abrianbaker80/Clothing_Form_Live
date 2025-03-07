<?php
/**
 * Clothing sizes data structure
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

return [
    'womens' => [
        'tops' => [
            'XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '1X', '2X', '3X', '4X',
            '00', '0', '2', '4', '6', '8', '10', '12', '14', '16', '18', '20', '22', '24'
        ],
        'bottoms' => [
            '00', '0', '2', '4', '6', '8', '10', '12', '14', '16', '18', '20', '22', '24',
            '24W', '26W', '28W', '30W', '32W', '34W', '36W', '38W', '40W', '42W', '44W'
        ],
        'dresses' => [
            'XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '1X', '2X', '3X', '4X',
            '00', '0', '2', '4', '6', '8', '10', '12', '14', '16', '18', '20', '22', '24'
        ],
        'shoes' => [
            '4', '4.5', '5', '5.5', '6', '6.5', '7', '7.5', '8', '8.5', '9', '9.5', '10', 
            '10.5', '11', '11.5', '12'
        ],
        'accessories' => ['One Size', 'XS', 'S', 'M', 'L', 'XL'],
        'default' => ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'One Size']
    ],
    'mens' => [
        'tops' => [
            'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL',
            '13', '13.5', '14', '14.5', '15', '15.5', '16', '16.5', '17', '17.5', '18',
            '34', '35', '36', '37', '38', '39', '40', '42', '44', '46', '48', '50', '52', '54'
        ],
        'bottoms' => [
            '28', '29', '30', '31', '32', '33', '34', '35', '36', '38', '40', '42', '44', '46', '48', '50',
            'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'
        ],
        'shoes' => [
            '6', '6.5', '7', '7.5', '8', '8.5', '9', '9.5', '10', '10.5', '11', '11.5', '12', 
            '12.5', '13', '13.5', '14', '15', '16'
        ],
        'accessories' => ['One Size', 'S', 'M', 'L', 'XL'],
        'default' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'One Size']
    ],
    'kids' => [
        'girls' => [
            '0-3m', '3-6m', '6-9m', '9-12m', '12-18m', '18-24m',
            '2T', '3T', '4T', '5T', '6', '7', '8', '10', '12', '14', '16'
        ],
        'boys' => [
            '0-3m', '3-6m', '6-9m', '9-12m', '12-18m', '18-24m',
            '2T', '3T', '4T', '5T', '6', '7', '8', '10', '12', '14', '16'
        ],
        'baby' => [
            'Preemie', 'Newborn', '0-3m', '3-6m', '6-9m', '9-12m', '12-18m', '18-24m'
        ],
        'default' => [
            'Preemie', 'Newborn', '0-3m', '3-6m', '6-9m', '9-12m', '12-18m', '18-24m',
            '2T', '3T', '4T', '5T', '6', '7', '8', '10', '12', '14', '16'
        ]
    ],
    // Default sizes to use when no specific match is found
    'default' => ['One Size', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL']
];
