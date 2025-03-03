<?php
/**
 * Clothing categories data structure
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

return [
    'womens' => [
        'name' => 'Women\'s',
        'subcategories' => [
            'tops' => [
                'name' => 'Tops',
                'subcategories' => [
                    'blouses' => ['name' => 'Blouses'],
                    't_shirts' => ['name' => 'T-Shirts'],
                    'sweaters' => ['name' => 'Sweaters'],
                    'tanks' => ['name' => 'Tank Tops'],
                    'button_up' => ['name' => 'Button-Up Shirts']
                ]
            ],
            'bottoms' => [
                'name' => 'Bottoms',
                'subcategories' => [
                    'pants' => ['name' => 'Pants'],
                    'jeans' => ['name' => 'Jeans'],
                    'skirts' => ['name' => 'Skirts'],
                    'shorts' => ['name' => 'Shorts']
                ]
            ],
            'dresses' => [
                'name' => 'Dresses',
                'subcategories' => [
                    'casual' => ['name' => 'Casual'],
                    'formal' => ['name' => 'Formal'],
                    'maxi' => ['name' => 'Maxi'],
                    'mini' => ['name' => 'Mini']
                ]
            ],
            'outerwear' => [
                'name' => 'Outerwear',
                'subcategories' => [
                    'jackets' => ['name' => 'Jackets'],
                    'coats' => ['name' => 'Coats'],
                    'blazers' => ['name' => 'Blazers']
                ]
            ],
            'activewear' => [
                'name' => 'Activewear',
                'subcategories' => [
                    'leggings' => ['name' => 'Leggings'],
                    'sports_bras' => ['name' => 'Sports Bras'],
                    'athletic_tops' => ['name' => 'Athletic Tops'],
                    'athletic_shorts' => ['name' => 'Athletic Shorts']
                ]
            ],
            'swimwear' => [
                'name' => 'Swimwear',
                'subcategories' => [
                    'one_piece' => ['name' => 'One Piece'],
                    'bikini' => ['name' => 'Bikini'],
                    'coverups' => ['name' => 'Cover-Ups']
                ]
            ],
            'shoes' => [
                'name' => 'Shoes',
                'subcategories' => [
                    'sneakers' => ['name' => 'Sneakers'],
                    'heels' => ['name' => 'Heels'],
                    'sandals' => ['name' => 'Sandals'],
                    'boots' => ['name' => 'Boots'],
                    'flats' => ['name' => 'Flats']
                ]
            ],
            'accessories' => [
                'name' => 'Accessories',
                'subcategories' => [
                    'jewelry' => ['name' => 'Jewelry'],
                    'bags' => ['name' => 'Bags'],
                    'scarves' => ['name' => 'Scarves'],
                    'hats' => ['name' => 'Hats']
                ]
            ]
        ]
    ],
    'mens' => [
        'name' => 'Men\'s',
        'subcategories' => [
            'tops' => [
                'name' => 'Tops',
                'subcategories' => [
                    't_shirts' => ['name' => 'T-Shirts'],
                    'polos' => ['name' => 'Polos'],
                    'button_up' => ['name' => 'Button-Up Shirts'],
                    'sweaters' => ['name' => 'Sweaters']
                ]
            ],
            'bottoms' => [
                'name' => 'Bottoms',
                'subcategories' => [
                    'pants' => ['name' => 'Pants'],
                    'jeans' => ['name' => 'Jeans'],
                    'shorts' => ['name' => 'Shorts']
                ]
            ],
            'outerwear' => [
                'name' => 'Outerwear',
                'subcategories' => [
                    'jackets' => ['name' => 'Jackets'],
                    'coats' => ['name' => 'Coats'],
                    'blazers' => ['name' => 'Blazers']
                ]
            ],
            'activewear' => [
                'name' => 'Activewear',
                'subcategories' => [
                    'athletic_tops' => ['name' => 'Athletic Tops'],
                    'athletic_shorts' => ['name' => 'Athletic Shorts'],
                    'sweatpants' => ['name' => 'Sweatpants']
                ]
            ],
            'shoes' => [
                'name' => 'Shoes',
                'subcategories' => [
                    'sneakers' => ['name' => 'Sneakers'],
                    'dress_shoes' => ['name' => 'Dress Shoes'],
                    'boots' => ['name' => 'Boots'],
                    'sandals' => ['name' => 'Sandals']
                ]
            ],
            'accessories' => [
                'name' => 'Accessories',
                'subcategories' => [
                    'ties' => ['name' => 'Ties'],
                    'watches' => ['name' => 'Watches'],
                    'belts' => ['name' => 'Belts'],
                    'hats' => ['name' => 'Hats']
                ]
            ]
        ]
    ],
    'kids' => [
        'name' => 'Kids',
        'subcategories' => [
            'girls' => [
                'name' => 'Girls',
                'subcategories' => [
                    'tops' => ['name' => 'Tops'],
                    'bottoms' => ['name' => 'Bottoms'],
                    'dresses' => ['name' => 'Dresses'],
                    'outerwear' => ['name' => 'Outerwear'],
                    'shoes' => ['name' => 'Shoes']
                ]
            ],
            'boys' => [
                'name' => 'Boys',
                'subcategories' => [
                    'tops' => ['name' => 'Tops'],
                    'bottoms' => ['name' => 'Bottoms'],
                    'outerwear' => ['name' => 'Outerwear'],
                    'shoes' => ['name' => 'Shoes']
                ]
            ],
            'baby' => [
                'name' => 'Baby',
                'subcategories' => [
                    'onesies' => ['name' => 'Onesies'],
                    'outfits' => ['name' => 'Outfits'],
                    'sleepwear' => ['name' => 'Sleepwear']
                ]
            ]
        ]
    ]
];