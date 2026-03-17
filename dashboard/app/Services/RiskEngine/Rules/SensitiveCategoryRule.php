<?php

namespace App\Services\RiskEngine\Rules;

use App\Models\Site;
use App\Models\UpdateJobItem;
use App\Services\RiskEngine\RiskRule;

class SensitiveCategoryRule implements RiskRule
{
    /**
     * Sensitive plugin slugs and their categories.
     * Extend this list as needed.
     */
    private const SENSITIVE_PLUGINS = [
        // E-commerce
        'woocommerce' => 'ecommerce',
        'easy-digital-downloads' => 'ecommerce',
        'surecart' => 'ecommerce',

        // Page builders
        'elementor' => 'page_builder',
        'js_composer' => 'page_builder',
        'beaver-builder-lite-version' => 'page_builder',
        'brizy' => 'page_builder',

        // Security
        'wordfence' => 'security',
        'better-wp-security' => 'security',
        'sucuri-scanner' => 'security',
        'all-in-one-wp-security-and-firewall' => 'security',

        // Caching
        'w3-total-cache' => 'caching',
        'wp-super-cache' => 'caching',
        'wp-rocket' => 'caching',
        'litespeed-cache' => 'caching',
        'wp-fastest-cache' => 'caching',

        // SEO
        'wordpress-seo' => 'seo',
        'all-in-one-seo-pack' => 'seo',
        'seo-by-rank-math' => 'seo',

        // Membership / access
        'memberpress' => 'membership',
        'restrict-content' => 'membership',

        // Forms with payment
        'gravityforms' => 'forms',
        'wpforms-lite' => 'forms',
    ];

    public function evaluate(UpdateJobItem $item, Site $site): ?array
    {
        if ($item->type !== 'plugin') {
            return null;
        }

        $category = self::SENSITIVE_PLUGINS[$item->slug] ?? null;

        if (! $category) {
            return null;
        }

        $labels = [
            'ecommerce' => 'commerce-critical',
            'page_builder' => 'page builder',
            'security' => 'security',
            'caching' => 'caching',
            'seo' => 'SEO',
            'membership' => 'membership/access',
            'forms' => 'forms/payment',
        ];

        return [
            'rule' => 'sensitive_category',
            'description' => "{$item->slug} is a {$labels[$category]} plugin — higher impact if update fails",
            'score' => 15,
            'data' => [
                'slug' => $item->slug,
                'category' => $category,
            ],
        ];
    }
}
