<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name_fr' => 'Électronique',
                'name_ar' => 'الإلكترونيات',
                'slug' => 'electronique',
                'description_fr' => 'Produits électroniques et accessoires',
                'description_ar' => 'المنتجات الإلكترونية والاكسسوارات',
                'is_active' => true,
                'sort_order' => 1,
                'children' => [
                    [
                        'name_fr' => 'Smartphones',
                        'name_ar' => 'الهواتف الذكية',
                        'slug' => 'smartphones',
                        'is_active' => true,
                        'sort_order' => 1,
                    ],
                    [
                        'name_fr' => 'Tablettes',
                        'name_ar' => 'الأجهزة اللوحية',
                        'slug' => 'tablettes',
                        'is_active' => true,
                        'sort_order' => 2,
                    ],
                    [
                        'name_fr' => 'Accessoires',
                        'name_ar' => 'الاكسسوارات',
                        'slug' => 'accessoires-electronique',
                        'is_active' => true,
                        'sort_order' => 3,
                    ],
                ],
            ],
            [
                'name_fr' => 'Mode & Textile',
                'name_ar' => 'الموضة والمنسوجات',
                'slug' => 'mode-textile',
                'description_fr' => 'Vêtements et accessoires de mode',
                'description_ar' => 'الملابس وإكسسوارات الموضة',
                'is_active' => true,
                'sort_order' => 2,
                'children' => [
                    [
                        'name_fr' => 'Vêtements Homme',
                        'name_ar' => 'ملابس رجالية',
                        'slug' => 'vetements-homme',
                        'is_active' => true,
                        'sort_order' => 1,
                    ],
                    [
                        'name_fr' => 'Vêtements Femme',
                        'name_ar' => 'ملابس نسائية',
                        'slug' => 'vetements-femme',
                        'is_active' => true,
                        'sort_order' => 2,
                    ],
                ],
            ],
            [
                'name_fr' => 'Alimentaire',
                'name_ar' => 'المواد الغذائية',
                'slug' => 'alimentaire',
                'description_fr' => 'Produits alimentaires et boissons',
                'description_ar' => 'المنتجات الغذائية والمشروبات',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name_fr' => 'Maison & Décoration',
                'name_ar' => 'المنزل والديكور',
                'slug' => 'maison-decoration',
                'description_fr' => 'Articles pour la maison et décoration',
                'description_ar' => 'أدوات منزلية وديكور',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $category = Category::create($categoryData);

            // Create subcategories
            foreach ($children as $childData) {
                $childData['parent_id'] = $category->id;
                Category::create($childData);
            }
        }

        $this->command->info('Categories created successfully!');
    }
}
