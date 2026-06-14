<?php
declare(strict_types=1);

function landing_page_csv_fields(PDO $pdo): array
{
    $fields = [
        'id',
        'slug',
        'city',
        'municipality',
        'area_type',
        'page_template',
        'region',
        'page_title',
        'meta_description',
        'hero_eyebrow',
        'hero_title',
        'hero_subtitle',
        'intro_title',
        'intro_text',
        'region_text',
        'seo_content_html',
        'faq_html',
        'is_active',
    ];

    $optionalFields = [
        'form_section_label',
        'form_section_title',
        'form_section_intro',
        'check_aside_title',
        'check_aside_text',
        'process_label',
        'process_title',
        'process_intro',
        'process_step_1_title',
        'process_step_1_text',
        'process_step_2_title',
        'process_step_2_text',
        'process_step_3_title',
        'process_step_3_text',
        'conversion_label',
        'conversion_title',
        'conversion_text',
        'trust_label',
        'trust_title',
        'trust_intro',
        'problems_label',
        'problems_title',
        'reviews_label',
        'reviews_title',
        'reviews_intro',
        'hero_badges_json',
        'trust_cards_json',
        'problem_cards_json',
    ];

    foreach ($optionalFields as $field) {
        if (app_column_exists($pdo, 'landing_pages', $field)) {
            $fields[] = $field;
        }
    }

    return $fields;
}

function landing_page_importable_fields(PDO $pdo): array
{
    return array_values(array_filter(
        landing_page_csv_fields($pdo),
        static fn (string $field): bool => $field !== 'id'
    ));
}

function landing_page_required_import_fields(): array
{
    return [
        'slug',
        'city',
        'municipality',
        'area_type',
        'page_template',
        'region',
        'page_title',
        'meta_description',
        'hero_eyebrow',
        'hero_title',
        'hero_subtitle',
        'intro_title',
        'intro_text',
        'region_text',
        'is_active',
    ];
}

