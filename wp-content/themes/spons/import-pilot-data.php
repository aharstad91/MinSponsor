<?php
/**
 * Import pilot data for MinSponsor
 * Run this file once via: /spons/wp-content/themes/spons/import-pilot-data.php
 * or via WP-CLI: wp eval-file import-pilot-data.php
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Only run for admin
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}

echo "<h1>Importing Pilot Data for MinSponsor</h1>";
echo "<pre>";

// Step 1: Create or get Klubb "Heimdal Håndball"
$klubb_title = 'Heimdal Håndball';
$klubb_slug = 'heimdal-handball';

$existing_klubb = get_page_by_path($klubb_slug, OBJECT, 'klubb');
if ($existing_klubb) {
    $klubb_id = $existing_klubb->ID;
    echo "✓ Klubb '{$klubb_title}' already exists (ID: {$klubb_id})\n";
} else {
    $klubb_id = wp_insert_post([
        'post_title' => $klubb_title,
        'post_name' => $klubb_slug,
        'post_type' => 'klubb',
        'post_status' => 'publish',
    ]);
    if (is_wp_error($klubb_id)) {
        echo "✗ Error creating klubb: " . $klubb_id->get_error_message() . "\n";
        die();
    }
    echo "✓ Created klubb '{$klubb_title}' (ID: {$klubb_id})\n";
}

// Step 2: Create or get Lag "Gutter 2009"
$lag_title = 'Gutter 2009';
$lag_slug = 'gutter-2009';

$existing_lag = get_page_by_path($lag_slug, OBJECT, 'lag');
if ($existing_lag) {
    $lag_id = $existing_lag->ID;
    echo "✓ Lag '{$lag_title}' already exists (ID: {$lag_id})\n";
    // Update parent klubb reference
    update_field('parent_klubb', $klubb_id, $lag_id);
} else {
    $lag_id = wp_insert_post([
        'post_title' => $lag_title,
        'post_name' => $lag_slug,
        'post_type' => 'lag',
        'post_status' => 'publish',
    ]);
    if (is_wp_error($lag_id)) {
        echo "✗ Error creating lag: " . $lag_id->get_error_message() . "\n";
        die();
    }
    // Set parent klubb via ACF field
    update_field('parent_klubb', $klubb_id, $lag_id);
    echo "✓ Created lag '{$lag_title}' (ID: {$lag_id})\n";
}

// Step 3: Create taxonomy term "Håndball" if it doesn't exist
$term = term_exists('Håndball', 'idrettsgren');
if (!$term) {
    $term = wp_insert_term('Håndball', 'idrettsgren');
    echo "✓ Created taxonomy term 'Håndball'\n";
} else {
    echo "✓ Taxonomy term 'Håndball' already exists\n";
}

// Assign term to lag
wp_set_object_terms($lag_id, 'Håndball', 'idrettsgren');
echo "✓ Assigned 'Håndball' to lag\n";

// Step 4: Create Spillere
$spillere = [
    'Anton Jimenez Hauge',
    'Lucas Lindstrøm Håve',
    'Linus Nergård Jensen',
    'Kristian Moe Ass',
    'Even Aspen Nordli',
    'Adrian Schjetne Aasen',
    'Oliver Langørgen Sagfjæra',
    'Julian Lindstrøm Håve',
    'Brage Johan Reitaas',
    'Espen Bergseth Larsen',
    'Gjermund Bremnes Myklebust',
    'Kevin Høgsnes Garte',
    'Matheo Nysæter Bakken',
    'Lukas Ulriksen',
    'Magnus Skogli',
    'Håkon Brevik Jensen',
    'Adrian Gripp-Johnsen',
    'Jakob Juvik',
    'Vidar Samdahl',
    'Sander Nilsen',
    'David Hovde Moen',
];

echo "\nCreating " . count($spillere) . " spillere:\n";
echo str_repeat('-', 50) . "\n";

$created_count = 0;
$existing_count = 0;

foreach ($spillere as $spiller_name) {
    // Generate slug
    $spiller_slug = sanitize_title($spiller_name);
    
    // Check if spiller already exists
    $existing = get_page_by_path($spiller_slug, OBJECT, 'spiller');
    
    if ($existing) {
        echo "  • '{$spiller_name}' already exists (ID: {$existing->ID})\n";
        // Update parent_lag reference to be sure
        update_field('parent_lag', $lag_id, $existing->ID);
        $existing_count++;
    } else {
        $spiller_id = wp_insert_post([
            'post_title' => $spiller_name,
            'post_name' => $spiller_slug,
            'post_type' => 'spiller',
            'post_status' => 'publish',
        ]);
        
        if (is_wp_error($spiller_id)) {
            echo "  ✗ Error creating '{$spiller_name}': " . $spiller_id->get_error_message() . "\n";
        } else {
            // Set parent lag via ACF field
            update_field('parent_lag', $lag_id, $spiller_id);
            echo "  ✓ Created '{$spiller_name}' (ID: {$spiller_id})\n";
            $created_count++;
        }
    }
}

echo str_repeat('-', 50) . "\n";
echo "Summary:\n";
echo "  - Klubb: {$klubb_title} (ID: {$klubb_id})\n";
echo "  - Lag: {$lag_title} (ID: {$lag_id})\n";
echo "  - Spillere created: {$created_count}\n";
echo "  - Spillere already existed: {$existing_count}\n";

// Flush rewrite rules
flush_rewrite_rules();
echo "\n✓ Flushed rewrite rules\n";

echo "\n</pre>";

// Show links
echo "<h2>Test Links:</h2>";
echo "<ul>";
echo "<li><a href='" . home_url("/stott/{$klubb_slug}/") . "' target='_blank'>Klubb: {$klubb_title}</a></li>";
echo "<li><a href='" . home_url("/stott/{$klubb_slug}/{$lag_slug}/") . "' target='_blank'>Lag: {$lag_title}</a></li>";
echo "<li><a href='" . home_url("/stott/{$klubb_slug}/{$lag_slug}/" . sanitize_title($spillere[0]) . "/") . "' target='_blank'>Spiller: {$spillere[0]}</a></li>";
echo "</ul>";

echo "<p style='color: green; font-weight: bold;'>✓ Import complete!</p>";
echo "<p><a href='" . admin_url('edit.php?post_type=spiller') . "'>View all spillere in admin</a></p>";
