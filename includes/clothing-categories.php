<?php
/**
 * Clothing categories structured hierarchically
 */
return [
    'gender' => [
        'male' => [
            'label' => 'Men',
            'categories' => [
                'tops' => [
                    'label' => 'Tops',
                    'subcategories' => [
                        'tshirts' => [
                            'label' => 'T-Shirts',
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL']
                        ],
                        'shirts' => [
                            'label' => 'Shirts',
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL']
                        ],
                        'sweaters' => [
                            'label' => 'Sweaters',
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL']
                        ],
                        'hoodies' => [
                            'label' => 'Hoodies',
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL']
                        ]
                    ]
                ],
                'bottoms' => [
                    'label' => 'Bottoms',
                    'subcategories' => [
                        'jeans' => [
                            'label' => 'Jeans',
                            'sizes' => ['28', '30', '32', '34', '36', '38', '40', '42', '44']
                        ],
                        'dress_pants' => [
                            'label' => 'Dress Pants',
                            'sizes' => ['28', '30', '32', '34', '36', '38', '40', '42', '44']
                        ],
                        'shorts' => [
                            'label' => 'Shorts',
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL']
                        ],
                        'sweatpants' => [
                            'label' => 'Sweatpants',
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL']
                        ]
                    ]
                ],
                'outerwear' => [
                    'label' => 'Outerwear',
                    'subcategories' => [
                        'jackets' => [
                            'label' => 'Jackets',
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL']
                        ],
                        'coats' => [
                            'label' => 'Coats',
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL']
                        ]
                    ]
                ],
                'footwear' => [
                    'label' => 'Footwear',
                    'subcategories' => [
                        'sneakers' => [
                            'label' => 'Sneakers',
                            'sizes' => ['7', '7.5', '8', '8.5', '9', '9.5', '10', '10.5', '11', '11.5', '12', '13']
                        ],
                        'formal' => [
                            'label' => 'Formal Shoes',
                            'sizes' => ['7', '7.5', '8', '8.5', '9', '9.5', '10', '10.5', '11', '11.5', '12', '13']
                        ],
                        'boots' => [
                            'label' => 'Boots',
                            'sizes' => ['7', '7.5', '8', '8.5', '9', '9.5', '10', '10.5', '11', '11.5', '12', '13']
                        ]
                    ]
                ]
            ]
        ],
        'female' => [
            'label' => 'Women',
            'categories' => [
                'tops' => [
                    'label' => 'Tops',
                    'subcategories' => [
                        'blouses' => [
                            'label' => 'Blouses',
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL']
                        ],
                        'tshirts' => [
                            'label' => 'T-Shirts',
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL']
                        ],
                        'sweaters' => [
                            'label' => 'Sweaters',
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL']
                        ]
                    ]
                ],
                'bottoms' => [
                    'label' => 'Bottoms',
                    'subcategories' => [
                        'jeans' => [
                            'label' => 'Jeans',
                            'sizes' => ['00', '0', '2', '4', '6', '8', '10', '12', '14', '16', '18', '20']
                        ],
                        'dress_pants' => [
                            'label' => 'Dress Pants',
                            'sizes' => ['00', '0', '2', '4', '6', '8', '10', '12', '14', '16', '18', '20']
                        ],
                        'shorts' => [
                            'label' => 'Shorts',
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL']
                        ],
                        'skirts' => [
                            'label' => 'Skirts',
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL']
                        ],
                        'leggings' => [
                            'label' => 'Leggings',
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL']
                        ]
                    ]
                ],
                'dresses' => [
                    'label' => 'Dresses',
                    'subcategories' => [
                        'casual' => [
                            'label' => 'Casual',
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL']
                        ],
                        'formal' => [
                            'label' => 'Formal',
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL']
                        ],
                        'maxi' => [
                            'label' => 'Maxi Dresses',
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL']
                        ]
                    ]
                ],
                'outerwear' => [
                    'label' => 'Outerwear',
                    'subcategories' => [
                        'jackets' => [
                            'label' => 'Jackets',
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL']
                        ],
                        'coats' => [
                            'label' => 'Coats',
                            'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL']
                        ]
                    ]
                ],
                'footwear' => [
                    'label' => 'Footwear',
                    'subcategories' => [
                        'heels' => [
                            'label' => 'Heels',
                            'sizes' => ['5', '5.5', '6', '6.5', '7', '7.5', '8', '8.5', '9', '9.5', '10']
                        ],
                        'flats' => [
                            'label' => 'Flats',
                            'sizes' => ['5', '5.5', '6', '6.5', '7', '7.5', '8', '8.5', '9', '9.5', '10']
                        ],
                        'sneakers' => [
                            'label' => 'Sneakers',
                            'sizes' => ['5', '5.5', '6', '6.5', '7', '7.5', '8', '8.5', '9', '9.5', '10']
                        ],
                        'boots' => [
                            'label' => 'Boots',
                            'sizes' => ['5', '5.5', '6', '6.5', '7', '7.5', '8', '8.5', '9', '9.5', '10']
                        ]
                    ]
                ]
            ]
        ],
        'kids' => [
            'label' => 'Kids',
            'categories' => [
                'tops' => [
                    'label' => 'Tops',
                    'subcategories' => [
                        'tshirts' => [
                            'label' => 'T-Shirts',
                            'sizes' => ['2T', '3T', '4T', '5', '6', '7', '8', '10', '12', '14', '16']
                        ],
                        'sweaters' => [
                            'label' => 'Sweaters',
                            'sizes' => ['2T', '3T', '4T', '5', '6', '7', '8', '10', '12', '14', '16']
                        ]
                    ]
                ],
                'bottoms' => [
                    'label' => 'Bottoms',
                    'subcategories' => [
                        'jeans' => [
                            'label' => 'Jeans',
                            'sizes' => ['2T', '3T', '4T', '5', '6', '7', '8', '10', '12', '14', '16']
                        ],
                        'shorts' => [
                            'label' => 'Shorts',
                            'sizes' => ['2T', '3T', '4T', '5', '6', '7', '8', '10', '12', '14', '16']
                        ]
                    ]
                ]
            ]
        ]
    ]
];