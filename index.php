<?php
/**
 * Template Name: Registration/Inscription Page
 * Template for displaying registration page with map and programs
 */
global $wp_query;
if ( $wp_query->is_singular() && $wp_query->get_queried_object() ) {
    $GLOBALS['post'] = $wp_query->get_queried_object();
    setup_postdata( $GLOBALS['post'] );
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php the_title(); ?> - <?php bloginfo('name'); ?></title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" 
          crossorigin=""/>
    
    <link href="https://fonts.cdnfonts.com/css/more-sugar" rel="stylesheet">
    <?php wp_head(); ?>
    <style>
        body { 
            font-family: Arial, sans-serif;
            background: #fff;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .section-wireframe {
            background: #fafafa;
            padding: 24px 20px;
            margin: 0;
        }
        .section-wireframe.p-0 {
            min-height: 600px;
        }
        .registration-hero-subtitle {
            font-family: 'Gill Sans', 'Gill Sans MT', Calibri, sans-serif;
        }
        .registration-hero-title {
            margin-top: 5rem;
            font-family: 'More Sugar', cursive;
            color: #fff;
            -webkit-text-stroke: 16px #000;
            paint-order: stroke fill;
            font-size: 8rem !important;
            letter-spacing: -0.05em;
        }
        .wireframe-box {
            border: 2px dashed #999;
            background: #f5f5f5;
            padding: 20px;
            margin: 10px 0;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 14px;
            text-align: center;
        }
        .hero-image-overlay {
            position: relative;
            background-size: cover;
            background-position: center;
            min-height: 700px;
        }
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .region-buttons-wrap {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 60px;
        }
        @media (max-width: 768px) {
            .region-buttons-wrap {
                gap: 12px;
                padding: 0 16px;
            }
            .region-button {
                min-width: 0;
                width: calc(50% - 6px);
                padding: 12px 16px;
                font-size: 0.95rem;
            }
            .registration-hero-title {
                font-size: 4rem !important;
                -webkit-text-stroke: 8px #000;
                margin-top: 2rem;
            }
        }
        .region-button {
            min-width: 240px;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.2s;
            margin: 0;
            background-color: #fff !important;
            color: #000 !important;
        }
        .region-button.active {
            background-color: #e6e6e6 !important;
            color: #000 !important;
        }
        .region-button:hover {
            background-color: #e6e6e6 !important;
            color: #000 !important;
            transform: scale(1.04);
        }
        .cta-buttons-wrap {
            gap: 40px;
            font-family: 'Gill Sans', 'Gill Sans MT', Calibri, sans-serif;
            font-size: 1.3rem;
        }
        .cta-buttons-wrap .region-button {
            padding: 18px 120px !important;
            font-family: 'Gill Sans', 'Gill Sans MT', Calibri, sans-serif;
            font-size: 1.3rem;
            min-width: 0;
        }
        @media (max-width: 768px) {
            .cta-buttons-wrap {
                gap: 16px;
                flex-direction: column;
                align-items: center;
            }
            .cta-buttons-wrap .btn {
                padding: 14px 32px !important;
                font-size: 1.1rem !important;
                width: 100%;
                max-width: 320px;
            }
        }
        .cta-section-dark .region-button:not(.active) {
            background: transparent;
            color: #fff;
            border: 2px solid #fff;
        }
        .cta-section-dark .region-button:not(.active):hover {
            background: #fff;
            color: #000;
            border-color: #fff;
        }
        .cta-btn-white,
        .cta-section-dark .cta-btn-white {
            background: #fff !important;
            color: #000 !important;
            border: none !important;
        }
        .cta-btn-white:hover,
        .cta-section-dark .cta-btn-white:hover {
            background: #e6e6e6 !important;
            color: #000 !important;
        }
        .cta-section-dark {
            margin-top: 48px;
            padding-top: 120px;
            padding-bottom: 140px;
            position: relative;
        }
        .cta-section-dark .cta-wave {
            position: absolute;
            top: -60px;
            left: 0;
            width: 100%;
            line-height: 0;
            overflow: visible;
            z-index: 1;
        }
        .cta-section-dark .cta-wave svg {
            width: 100%;
            height: 60px;
            display: block;
            vertical-align: top;
        }
        .cta-section-dark .cta-wave path {
            fill: #000;
        }
        .cta-section-dark .cta-wave {
            background: #fafafa;
        }
        
        /* Map Styles */
        .map-wrapper {
            position: relative;
        }
        #programMap {
            height: 500px;
            width: 100%;
            border: 2px solid #ddd;
            border-radius: 8px;
            z-index: 1;
        }
        .map-legend {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
            background: white;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 0.75rem;
            max-width: 200px;
        }
        .map-legend-note-card {
            position: relative;
            margin-top: 10px;
        }
        .map-legend-note-back {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 100%;
            background: #d62829;
            border-radius: 6px;
            z-index: 0;
        }
        .map-legend-note-front {
            position: relative;
            z-index: 1;
            margin-left: 8px;
            padding: 8px 10px;
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            font-size: 0.65rem;
            line-height: 1.3;
            color: #666;
        }
        .map-legend-note-front strong {
            color: #d62829;
        }
        .map-legend .fw-bold {
            font-size: 0.7rem;
            margin-bottom: 6px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 4px;
        }
        .legend-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
            flex-shrink: 0;
        }
        .legend-dot.soccer { background-color: #dc3545; }
        .legend-dot.dek-hockey { background-color: #0d6efd; }
        .legend-dot.multi-sport { background-color: #ffc107; }
        
        /* Program Card Styles */
        .programs-scroll {
            max-height: 500px;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 6px;
        }
        .programs-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .programs-scroll::-webkit-scrollbar-track {
            background: #f0f0f0;
            border-radius: 3px;
        }
        .programs-scroll::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 3px;
        }
        .program-card {
            --card-accent: #d62829;
            --card-accent-hover: #c52223;
            --card-accent-contrast: #ffffff;
            --card-accent-shadow: rgba(214, 40, 41, 0.25);
            background: white;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 20px 20px 12px 20px;
            margin-bottom: 20px;
            transition: transform 0.3s;
            overflow: hidden;
        }
        .program-card-active {
            border-color: var(--card-accent) !important;
            box-shadow: 0 0 0 3px var(--card-accent-shadow);
        }
        .program-card.program-card-soccer {
            --card-accent: #dc3545;
            --card-accent-hover: #bb2d3b;
            --card-accent-contrast: #ffffff;
            --card-accent-shadow: rgba(220, 53, 69, 0.25);
            background: #fff5f6;
            border-color: #dc3545;
        }
        .program-card.program-card-dek-hockey {
            --card-accent: #0d6efd;
            --card-accent-hover: #0b5ed7;
            --card-accent-contrast: #ffffff;
            --card-accent-shadow: rgba(13, 110, 253, 0.25);
            background: #f2f7ff;
            border-color: #0d6efd;
        }
        .program-card.program-card-multi-sport {
            --card-accent: #ffc107;
            --card-accent-hover: #e0a800;
            --card-accent-contrast: #222222;
            --card-accent-shadow: rgba(255, 193, 7, 0.3);
            background: #fff9e6;
            border-color: #ffc107;
        }
        .program-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .program-card-image {
            width: calc(100% + 40px);
            height: 100px;
            margin: -20px -20px 0 -20px;
            padding: 0;
            background: #eee;
            overflow: hidden;
        }
        .program-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .program-price-buttons-row {
            margin-top: 6px;
            padding-top: 10px;
            padding-bottom: 0;
            border-top: 1px solid #ddd;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 8px 12px;
        }
        .program-card-buttons {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            margin-left: auto;
        }
        .program-card-buttons .btn {
            min-width: 0;
            flex: 0 1 auto;
            padding: 11px 18px;
            font-size: 0.95rem;
            font-weight: 600;
            border: none;
            border-radius: 30px;
            background-color: var(--card-accent);
            color: var(--card-accent-contrast);
            text-align: center;
        }
        @media (max-width: 520px) {
            .program-card-buttons {
                width: 100%;
                justify-content: stretch;
            }
            .program-card-buttons .btn {
                flex: 1 1 calc(50% - 4px);
                min-width: min(100%, 9rem);
            }
            .program-card-buttons .btn:only-child {
                flex: 1 1 100%;
                min-width: 0;
            }
        }
        .program-card-buttons .btn:hover {
            background-color: var(--card-accent-hover);
            color: var(--card-accent-contrast);
        }
        .program-title-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 12px;
            margin-bottom: 8px;
            background-color: var(--card-accent);
            color: var(--card-accent-contrast);
        }
        .program-price {
            color: var(--card-accent);
        }
        .program-details {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 0;
        }
        .program-details-heading {
            font-weight: 700;
            font-size: 1.25rem;
            color: #000;
            margin-bottom: 4px;
        }
        .program-details-location-heading {
            font-weight: 700;
            margin-bottom: 4px;
        }
        .program-details-location {
            margin-bottom: 4px;
        }
        .program-details-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem 0.75rem;
        }
        .program-detail-item {
            margin-bottom: 0;
        }
        .filter-section {
            padding: 20px;
            border-radius: 8px;
        }
        .filter-section select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.95rem;
        }
        .filter-checkbox-dropdown {
            position: relative;
        }
        .filter-dropdown-toggle {
            display: block;
            width: 100%;
            padding: 10px 36px 10px 12px;
            font-size: 0.95rem;
            background: #fff url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") no-repeat right 12px center / 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: left;
            color: #333;
            line-height: 1.5;
        }
        .filter-dropdown-toggle:hover {
            border-color: #aaa;
        }
        .filter-dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 100;
            background: #fff;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 6px 6px;
            max-height: 220px;
            overflow-y: auto;
            padding: 6px 0;
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }
        .filter-dropdown-menu.show {
            display: block;
        }
        .filter-checkbox-label {
            display: flex;
            align-items: center;
            padding: 7px 12px;
            margin: 0;
            cursor: pointer;
            font-size: 0.9rem;
            gap: 8px;
            transition: background 0.15s;
            user-select: none;
        }
        .filter-checkbox-label:hover {
            background: #f5f5f5;
        }
        .filter-checkbox-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #d62829;
            flex-shrink: 0;
            cursor: pointer;
        }
    </style>
</head>
<body <?php body_class(); ?>>

<!-- Hero Section -->
<section class="section-wireframe p-0">
    <?php 
    $hero_image = rwmb_meta( 'registration_hero_image' );
    $hero_title = rwmb_meta( 'registration_hero_title' );
    $hero_subtitle = rwmb_meta( 'registration_hero_subtitle' );
    $hero_image_id = sevensports_first_image_id( $hero_image );
    
    if ( $hero_image_id ):
        $hero_image_url = wp_get_attachment_image_url( $hero_image_id, 'full' );
    ?>
        <div class="hero-image-overlay" style="background-image: url('<?php echo esc_url($hero_image_url); ?>');">
            <div class="hero-overlay">
                <div class="container text-center text-white">
                    <?php if ( $hero_title ): ?>
                        <h1 class="display-2 fw-bold mb-3 registration-hero-title" style="font-size: 4rem;"><?php echo esc_html($hero_title); ?></h1>
                    <?php endif; ?>
                    
                    <?php if ( $hero_subtitle ): ?>
                        <p class="fs-4 mb-5 registration-hero-subtitle"><?php echo esc_html($hero_subtitle); ?></p>
                    <?php endif; ?>
                    
                    <!-- Region Buttons -->
                    <div class="region-buttons-wrap">
                        <?php 
                        for ($i = 1; $i <= 4; $i++):
                            $region_name = rwmb_meta("region_{$i}_name");
                            $region_link = rwmb_meta("region_{$i}_link");
                            if ( $region_name ):
                        ?>
                            <button class="region-button <?php echo ($i === 1) ? 'active' : ''; ?>" 
                                    onclick="<?php echo $region_link ? "window.location.href='" . esc_url($region_link) . "'" : "filterByRegion('" . esc_js($region_name) . "')"; ?>">
                                <?php echo esc_html($region_name); ?>
                            </button>
                        <?php 
                            endif;
                        endfor; 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="container text-center py-5">
            <?php if ( $hero_title ): ?>
                <h1 class="display-2 fw-bold mb-3 registration-hero-title"><?php echo esc_html($hero_title); ?></h1>
            <?php else: ?>
                <div class="wireframe-box mx-auto mb-3" style="max-width: 500px;">REGISTRATION HERO TITLE</div>
            <?php endif; ?>
            
            <?php if ( $hero_subtitle ): ?>
                <p class="fs-4 mb-5"><?php echo esc_html($hero_subtitle); ?></p>
            <?php endif; ?>
            
            <!-- Region Buttons (Wireframe) -->
            <div class="region-buttons-wrap">
                <?php for ($i = 1; $i <= 4; $i++): 
                    $region_name = rwmb_meta("region_{$i}_name");
                    if ( $region_name ):
                ?>
                    <button class="region-button <?php echo ($i === 1) ? 'active' : ''; ?>">
                        <?php echo esc_html($region_name); ?>
                    </button>
                <?php 
                    else:
                ?>
                    <div class="wireframe-box" style="min-width: 180px;">REGION <?php echo $i; ?></div>
                <?php 
                    endif;
                endfor; 
                ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php
// Load all program data here so filter dropdowns (rendered before the list) have the variables they need.
$reg_page_id = get_queried_object_id();
if ( ! $reg_page_id && function_exists( 'is_singular' ) && is_singular( 'page' ) ) {
    global $wp_query;
    $reg_page_id = $wp_query->get_queried_object_id();
}
if ( ! $reg_page_id ) {
    $reg_page = get_page_by_path( 'registration' );
    if ( $reg_page ) {
        $reg_page_id = $reg_page->ID;
    }
}
if ( ! $reg_page_id ) {
    $pages = get_pages( array( 'meta_key' => '_wp_page_template', 'meta_value' => 'template-registration.php', 'number' => 1 ) );
    if ( ! empty( $pages ) ) {
        $reg_page_id = $pages[0]->ID;
    }
}
if ( ! $reg_page_id ) {
    $reg_page_id = get_the_ID();
}
$programs_title = $reg_page_id ? ( function_exists( 'rwmb_meta' ) ? rwmb_meta( 'programs_list_title', array(), $reg_page_id ) : '' ) : '';
if ( empty( $programs_title ) && $reg_page_id ) {
    $programs_title = get_post_meta( $reg_page_id, 'programs_list_title', true );
}
/**
 * Qidigo JSON (optional): wp-content/qidigo/programs.json or QIDIGO_PROGRAMS_JSON_PATH.
 *
 * Enhanced JSON (scraper v2): JSON is the data source; WordPress only supplies program_image
 * and optional trial_link override, matched by post meta qidigo_link === row link (normalized).
 * Scraper can set trial_link from the Qidigo group page (e.g. Essai Gratuit → Eventnroll).
 * Program posts with no qidigo_link (or link not in this feed) are merged in as manual rows.
 * Fallback image: filter `sevensports_qidigo_fallback_program_image_url` or
 * child/theme `images/program-default.jpg` if that file exists.
 *
 * Remote JSON (default): GitHub raw programs.json. Override in wp-config.php:
 *   define( 'QIDIGO_PROGRAMS_URL', 'https://example.com/your-feed.json' );
 * Use local file only: define( 'QIDIGO_PROGRAMS_URL', '' );
 * Filter: `sevensports_qidigo_programs_url`. If the remote request fails, falls back to local file.
 *
 * Legacy JSON (older rows without `address`): previous merge rules still apply for that row.
 */
if ( ! function_exists( 'sevensports_normalize_qidigo_url' ) ) {
	function sevensports_normalize_qidigo_url( $url ) {
		$url = trim( (string) $url );
		if ( $url === '' ) {
			return '';
		}
		return strtolower( untrailingslashit( $url ) );
	}
}

if ( ! function_exists( 'sevensports_qidigo_heading_from_title' ) ) {
	function sevensports_qidigo_heading_from_title( $title ) {
		$title = trim( (string) $title );
		if ( $title === '' ) {
			return '';
		}
		$parts = explode( '|', $title, 2 );
		return trim( $parts[0] );
	}
}

if ( ! function_exists( 'sevensports_qidigo_format_address' ) ) {
	function sevensports_qidigo_format_address( $addr ) {
		if ( ! is_array( $addr ) ) {
			return '';
		}
		$street = isset( $addr['street'] ) ? trim( (string) $addr['street'] ) : '';
		$city   = isset( $addr['city'] ) ? trim( (string) $addr['city'] ) : '';
		$region = isset( $addr['region'] ) ? trim( (string) $addr['region'] ) : '';
		$postal = isset( $addr['postal_code'] ) ? trim( (string) $addr['postal_code'] ) : '';
		$tail   = trim( $region . ( $postal !== '' ? ' ' . $postal : '' ) );
		$parts  = array_filter( array( $street, $city, $tail ) );
		return implode( ', ', $parts );
	}
}

if ( ! function_exists( 'sevensports_qidigo_sport_slug' ) ) {
	function sevensports_qidigo_sport_slug( $sport_raw ) {
		$u = strtoupper( trim( (string) $sport_raw ) );
		if ( $u === '' ) {
			return '';
		}
		if ( strpos( $u, 'MULTISPORT' ) !== false || preg_match( '/\bMULTI\b/', $u ) ) {
			return 'multi_sport';
		}
		if ( strpos( $u, 'SOCCER' ) !== false ) {
			return 'soccer';
		}
		if ( strpos( $u, 'HOCKEY' ) !== false ) {
			return 'dek_hockey';
		}
		return strtolower( preg_replace( '/[^a-z0-9]+/i', '_', trim( $sport_raw ) ) );
	}
}

if ( ! function_exists( 'sevensports_normalize_city_for_region' ) ) {
	/**
	 * Normalize city string for lookup in sevensports_default_city_region_map().
	 */
	function sevensports_normalize_city_for_region( $city ) {
		$c = trim( remove_accents( strtolower( (string) $city ) ) );
		$c = str_replace( array( "'", '’', '`' ), '', $c );
		$c = preg_replace( '/\s+/', '-', $c );
		return $c;
	}
}

if ( ! function_exists( 'sevensports_default_city_region_map' ) ) {
	/**
	 * City (normalized) → region slug: montreal_monteregie | outaouais | estrie.
	 * Override or extend with filter `sevensports_city_region_map`.
	 */
	function sevensports_default_city_region_map() {
		return array(
			// Montréal / Montérégie
			'beloeil'                         => 'montreal_monteregie',
			'boucherville'                    => 'montreal_monteregie',
			'chambly'                         => 'montreal_monteregie',
			'chateauguay'                     => 'montreal_monteregie',
			'coteau-du-lac'                   => 'montreal_monteregie',
			'franklin'                        => 'montreal_monteregie',
			'longueuil'                       => 'montreal_monteregie',
			'montreal'                        => 'montreal_monteregie',
			'pointe-des-cascades'             => 'montreal_monteregie',
			'saint-etienne-de-beauharnois'    => 'montreal_monteregie',
			'saint-hyacinthe'                 => 'montreal_monteregie',
			'saint-jean-sur-richelieu'        => 'montreal_monteregie',
			'saint-louis-de-gonzague'         => 'montreal_monteregie',
			'sainte-anne-de-bellevue'         => 'montreal_monteregie',
			'sainte-catherine'                => 'montreal_monteregie',
			'sainte-clotilde-de-chateauguay'  => 'montreal_monteregie',
			'sainte-julie'                    => 'montreal_monteregie',
			'salaberry-de-valleyfield'        => 'montreal_monteregie',
			'varennes'                        => 'montreal_monteregie',
			// Outaouais
			'gatineau'                        => 'outaouais',
			'lange-gardien'                   => 'outaouais',
			// Estrie
			'bromont'                         => 'estrie',
			'cowansville'                     => 'estrie',
			'farnham'                         => 'estrie',
			'roxton-pond'                     => 'estrie',
			'sherbrooke'                      => 'estrie',
		);
	}
}

if ( ! function_exists( 'sevensports_city_to_region_slug' ) ) {
	/**
	 * @return string montreal_monteregie|outaouais|estrie or '' if unknown
	 */
	function sevensports_city_to_region_slug( $city ) {
		$k = sevensports_normalize_city_for_region( $city );
		if ( $k === '' ) {
			return '';
		}
		static $map = null;
		if ( $map === null ) {
			$map = apply_filters( 'sevensports_city_region_map', sevensports_default_city_region_map() );
		}
		if ( isset( $map[ $k ] ) ) {
			return (string) $map[ $k ];
		}
		return (string) apply_filters( 'sevensports_unknown_city_region_slug', '', $city, $k );
	}
}

if ( ! function_exists( 'sevensports_wp_program_row_from_post' ) ) {
	/**
	 * One list row from a published `program` CPT post (manual / partner, not from Qidigo JSON).
	 *
	 * @param WP_Post $p Program post.
	 * @return array<string, string>|null
	 */
	function sevensports_wp_program_row_from_post( $p ) {
		if ( ! $p instanceof WP_Post || $p->post_type !== 'program' ) {
			return null;
		}
		$program_image_raw = function_exists( 'rwmb_meta' ) ? rwmb_meta( 'program_image', array(), $p->ID ) : get_post_meta( $p->ID, 'program_image', true );
		$program_image_id  = null;
		if ( is_numeric( $program_image_raw ) ) {
			$program_image_id = (int) $program_image_raw;
		} elseif ( is_array( $program_image_raw ) && function_exists( 'sevensports_first_image_id' ) ) {
			$program_image_id = sevensports_first_image_id( $program_image_raw );
		}
		$program_image_url = $program_image_id ? wp_get_attachment_image_url( $program_image_id, 'medium' ) : get_the_post_thumbnail_url( $p->ID, 'medium' );
		$wp_city = trim( (string) get_post_meta( $p->ID, 'location_city', true ) );
		return array(
			'program_name'       => $p->post_title,
			'program_image_url'  => is_string( $program_image_url ) ? $program_image_url : '',
			'sport_type'         => trim( (string) get_post_meta( $p->ID, 'sport_type', true ) ),
			'sport_label'        => '',
			'location_heading'   => get_post_meta( $p->ID, 'location_heading', true ),
			'location_address'   => get_post_meta( $p->ID, 'location_address', true ),
			'location_city'      => $wp_city,
			'location_region'    => sevensports_city_to_region_slug( $wp_city ),
			'location_latitude'  => get_post_meta( $p->ID, 'location_latitude', true ),
			'location_longitude' => get_post_meta( $p->ID, 'location_longitude', true ),
			'age_range'          => get_post_meta( $p->ID, 'age_range', true ),
			'program_type'       => get_post_meta( $p->ID, 'program_type', true ),
			'season'             => get_post_meta( $p->ID, 'season', true ),
			'schedule'           => get_post_meta( $p->ID, 'schedule', true ),
			'price'              => get_post_meta( $p->ID, 'price', true ),
			'inscription_link'   => get_post_meta( $p->ID, 'inscription_link', true ),
			'trial_link'         => get_post_meta( $p->ID, 'trial_link', true ),
		);
	}
}

$programs = array();

$qidigo_rows = null;

$qidigo_default_programs_url = 'https://raw.githubusercontent.com/Malik1234567891011/7sportsjson/refs/heads/main/programs.json';

$qidigo_remote_url = '';
if ( defined( 'QIDIGO_PROGRAMS_URL' ) ) {
	$qidigo_remote_url = trim( (string) QIDIGO_PROGRAMS_URL );
} else {
	$qidigo_remote_url = $qidigo_default_programs_url;
}
$qidigo_remote_url = apply_filters( 'sevensports_qidigo_programs_url', $qidigo_remote_url );

if ( $qidigo_remote_url !== '' ) {
	$response = wp_remote_get(
		$qidigo_remote_url,
		array(
			'timeout'   => 45,
			'sslverify' => true,
		)
	);
	if ( ! is_wp_error( $response ) && (int) wp_remote_retrieve_response_code( $response ) === 200 ) {
		$qidigo_raw = wp_remote_retrieve_body( $response );
		$qidigo_dec = json_decode( $qidigo_raw, true );
		if ( is_array( $qidigo_dec ) ) {
			$qidigo_rows = $qidigo_dec;
		}
	}
}

if ( $qidigo_rows === null ) {
	$qidigo_json_path = defined( 'QIDIGO_PROGRAMS_JSON_PATH' ) && QIDIGO_PROGRAMS_JSON_PATH
		? QIDIGO_PROGRAMS_JSON_PATH
		: WP_CONTENT_DIR . '/qidigo/programs.json';

	if ( is_readable( $qidigo_json_path ) ) {
		$qidigo_raw = file_get_contents( $qidigo_json_path );
		$qidigo_dec = json_decode( $qidigo_raw, true );
		if ( is_array( $qidigo_dec ) ) {
			$qidigo_rows = $qidigo_dec;
		}
	}
}

if ( $qidigo_rows !== null ) {
	$program_posts = get_posts(
		array(
			'post_type'   => 'program',
			'numberposts' => -1,
			'orderby'     => 'menu_order title',
			'order'       => 'ASC',
			'post_status' => 'publish',
		)
	);
	$posts_by_qidigo = array();
	foreach ( $program_posts as $p ) {
		$ql = function_exists( 'rwmb_meta' ) ? rwmb_meta( 'qidigo_link', array(), $p->ID ) : get_post_meta( $p->ID, 'qidigo_link', true );
		$ql = sevensports_normalize_qidigo_url( $ql );
		if ( $ql !== '' ) {
			$posts_by_qidigo[ $ql ] = $p;
		}
	}

	$qidigo_fallback_image_url = '';
	foreach (
		array(
			array( get_stylesheet_directory() . '/images/program-default.jpg', get_stylesheet_directory_uri() . '/images/program-default.jpg' ),
			array( get_template_directory() . '/images/program-default.jpg', get_template_directory_uri() . '/images/program-default.jpg' ),
		) as $qidigo_fb_pair
	) {
		if ( file_exists( $qidigo_fb_pair[0] ) ) {
			$qidigo_fallback_image_url = $qidigo_fb_pair[1];
			break;
		}
	}
	$qidigo_fallback_image_url = apply_filters( 'sevensports_qidigo_fallback_program_image_url', $qidigo_fallback_image_url );

	foreach ( $qidigo_rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$link = isset( $row['link'] ) ? trim( (string) $row['link'] ) : '';
		$norm = sevensports_normalize_qidigo_url( $link );
		$p    = ( $norm !== '' && isset( $posts_by_qidigo[ $norm ] ) ) ? $posts_by_qidigo[ $norm ] : null;

		$program_image_url = '';
		if ( $p ) {
			$program_image_raw = function_exists( 'rwmb_meta' ) ? rwmb_meta( 'program_image', array(), $p->ID ) : get_post_meta( $p->ID, 'program_image', true );
			$program_image_id  = null;
			if ( is_numeric( $program_image_raw ) ) {
				$program_image_id = (int) $program_image_raw;
			} elseif ( is_array( $program_image_raw ) && function_exists( 'sevensports_first_image_id' ) ) {
				$program_image_id = sevensports_first_image_id( $program_image_raw );
			}
			$img = $program_image_id ? wp_get_attachment_image_url( $program_image_id, 'medium' ) : get_the_post_thumbnail_url( $p->ID, 'medium' );
			$program_image_url = is_string( $img ) ? $img : '';
		}
		if ( $program_image_url === '' && $qidigo_fallback_image_url !== '' ) {
			$program_image_url = $qidigo_fallback_image_url;
		}

		$is_enhanced = isset( $row['address'] ) && is_array( $row['address'] );

		if ( $is_enhanced ) {
			$title    = isset( $row['title'] ) ? trim( (string) $row['title'] ) : '';
			$schedule = isset( $row['schedule'] ) ? trim( (string) $row['schedule'] ) : '';
			$duration = isset( $row['duration'] ) ? trim( (string) $row['duration'] ) : '';
			$sched_out = $schedule;
			if ( $duration !== '' ) {
				$sched_out = $schedule === '' ? $duration : $schedule . ' · ' . $duration;
			}
			$json_city = isset( $row['city'] ) ? trim( (string) $row['city'] ) : '';
			$sport_raw = isset( $row['sport'] ) ? trim( (string) $row['sport'] ) : '';
			$age_r     = isset( $row['age_range'] ) ? trim( (string) $row['age_range'] ) : '';
			$addr_line = sevensports_qidigo_format_address( $row['address'] );
			$loc_name  = isset( $row['location_name'] ) ? trim( (string) $row['location_name'] ) : '';
			$city_f    = $json_city !== '' ? $json_city : ( isset( $row['address']['city'] ) ? trim( (string) $row['address']['city'] ) : '' );
			$spots     = isset( $row['remaining_spots'] ) ? trim( (string) $row['remaining_spots'] ) : '';
			$spots_line = $spots !== '' ? 'Spots: ' . $spots : '';
			$sport_slug = sevensports_qidigo_sport_slug( $sport_raw );

			$qidigo_season = isset( $row['season'] ) && $row['season'] !== null && $row['season'] !== ''
				? trim( (string) $row['season'] ) : '';
			$qidigo_lat    = isset( $row['latitude'] ) && $row['latitude'] !== null && $row['latitude'] !== ''
				? trim( (string) $row['latitude'] ) : '';
			$qidigo_lng    = isset( $row['longitude'] ) && $row['longitude'] !== null && $row['longitude'] !== ''
				? trim( (string) $row['longitude'] ) : '';
			$json_trial    = isset( $row['trial_link'] ) ? trim( (string) $row['trial_link'] ) : '';
			$wp_trial      = $p ? trim( (string) get_post_meta( $p->ID, 'trial_link', true ) ) : '';

			$programs[] = array(
				'program_name'       => $title,
				'program_image_url'  => $program_image_url,
				'sport_type'         => $sport_slug,
				'sport_label'        => $sport_raw,
				'location_heading'   => $loc_name !== '' ? $loc_name : ( $city_f !== '' ? $city_f : 'Location' ),
				'location_address'   => $addr_line,
				'location_city'      => $city_f,
				'location_region'    => sevensports_city_to_region_slug( $city_f ),
				'location_latitude'  => $qidigo_lat,
				'location_longitude' => $qidigo_lng,
				'age_range'          => $age_r,
				'program_type'       => $spots_line,
				'season'             => $qidigo_season,
				'schedule'           => $sched_out,
				'price'              => isset( $row['price'] ) ? trim( (string) $row['price'] ) : '',
				'inscription_link'   => $link,
				'trial_link'         => $wp_trial !== '' ? $wp_trial : $json_trial,
			);
			continue;
		}

		// Legacy JSON row (pre–contact scraper): keep prior merge behaviour.
		$title    = isset( $row['title'] ) ? trim( (string) $row['title'] ) : '';
		$schedule = isset( $row['schedule'] ) ? trim( (string) $row['schedule'] ) : '';
		$duration = isset( $row['duration'] ) ? trim( (string) $row['duration'] ) : '';
		$sched_out = $schedule;
		if ( $duration !== '' ) {
			$sched_out = $schedule === '' ? $duration : $schedule . ' · ' . $duration;
		}

		$age_json   = isset( $row['age'] ) ? trim( (string) $row['age'] ) : '';
		$spots      = isset( $row['remaining_spots'] ) ? trim( (string) $row['remaining_spots'] ) : '';
		$spots_line = $spots !== '' ? 'Spots: ' . $spots : '';
		$json_trial = isset( $row['trial_link'] ) ? trim( (string) $row['trial_link'] ) : '';
		$wp_trial   = $p ? trim( (string) get_post_meta( $p->ID, 'trial_link', true ) ) : '';
		$legacy_city = $p ? trim( (string) get_post_meta( $p->ID, 'location_city', true ) ) : '';

		$programs[] = array(
			'program_name'       => $title !== '' ? $title : ( $p ? $p->post_title : '' ),
			'program_image_url'  => $program_image_url,
			'sport_type'         => $p ? trim( (string) get_post_meta( $p->ID, 'sport_type', true ) ) : '',
			'sport_label'        => '',
			'location_heading'   => $p ? get_post_meta( $p->ID, 'location_heading', true ) : sevensports_qidigo_heading_from_title( $title ),
			'location_address'   => $p ? get_post_meta( $p->ID, 'location_address', true ) : '',
			'location_city'      => $legacy_city,
			'location_region'    => sevensports_city_to_region_slug( $legacy_city ),
			'location_latitude'  => $p ? get_post_meta( $p->ID, 'location_latitude', true ) : '',
			'location_longitude' => $p ? get_post_meta( $p->ID, 'location_longitude', true ) : '',
			'age_range'          => $age_json !== '' ? $age_json : ( $p ? get_post_meta( $p->ID, 'age_range', true ) : '' ),
			'program_type'       => $p ? get_post_meta( $p->ID, 'program_type', true ) : $spots_line,
			'season'             => $p ? get_post_meta( $p->ID, 'season', true ) : '',
			'schedule'           => $sched_out !== '' ? $sched_out : ( $p ? get_post_meta( $p->ID, 'schedule', true ) : '' ),
			'price'              => isset( $row['price'] ) ? trim( (string) $row['price'] ) : '',
			'inscription_link'   => $link,
			'trial_link'         => $wp_trial !== '' ? $wp_trial : $json_trial,
		);

		if ( $p && $spots_line !== '' ) {
			$ptype = trim( (string) $programs[ count( $programs ) - 1 ]['program_type'] );
			if ( $ptype !== '' && strpos( $ptype, $spots ) === false ) {
				$programs[ count( $programs ) - 1 ]['program_type'] = $ptype . ' · ' . $spots_line;
			} elseif ( $ptype === '' ) {
				$programs[ count( $programs ) - 1 ]['program_type'] = $spots_line;
			}
		}
	}

	$qidigo_links_in_feed = array();
	foreach ( $qidigo_rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$feed_link = isset( $row['link'] ) ? trim( (string) $row['link'] ) : '';
		$feed_norm = sevensports_normalize_qidigo_url( $feed_link );
		if ( $feed_norm !== '' ) {
			$qidigo_links_in_feed[ $feed_norm ] = true;
		}
	}
	foreach ( $program_posts as $p ) {
		$ql = function_exists( 'rwmb_meta' ) ? rwmb_meta( 'qidigo_link', array(), $p->ID ) : get_post_meta( $p->ID, 'qidigo_link', true );
		$ql = sevensports_normalize_qidigo_url( $ql );
		if ( $ql !== '' && isset( $qidigo_links_in_feed[ $ql ] ) ) {
			continue;
		}
		$manual_row = sevensports_wp_program_row_from_post( $p );
		if ( $manual_row !== null ) {
			$programs[] = $manual_row;
		}
	}
} else {
	$program_posts = get_posts(
		array(
			'post_type'   => 'program',
			'numberposts' => -1,
			'orderby'     => 'menu_order title',
			'order'       => 'ASC',
			'post_status' => 'publish',
		)
	);
	foreach ( $program_posts as $p ) {
		$program_image_raw = function_exists( 'rwmb_meta' ) ? rwmb_meta( 'program_image', array(), $p->ID ) : get_post_meta( $p->ID, 'program_image', true );
		$program_image_id  = null;
		if ( is_numeric( $program_image_raw ) ) {
			$program_image_id = (int) $program_image_raw;
		} elseif ( is_array( $program_image_raw ) && function_exists( 'sevensports_first_image_id' ) ) {
			$program_image_id = sevensports_first_image_id( $program_image_raw );
		}
		$program_image_url = $program_image_id ? wp_get_attachment_image_url( $program_image_id, 'medium' ) : get_the_post_thumbnail_url( $p->ID, 'medium' );
		$only_wp_city      = trim( (string) get_post_meta( $p->ID, 'location_city', true ) );
		$programs[]        = array(
			'program_name'       => $p->post_title,
			'program_image_url'  => is_string( $program_image_url ) ? $program_image_url : '',
			'sport_type'         => trim( (string) get_post_meta( $p->ID, 'sport_type', true ) ),
			'sport_label'        => '',
			'location_heading'   => get_post_meta( $p->ID, 'location_heading', true ),
			'location_address'   => get_post_meta( $p->ID, 'location_address', true ),
			'location_city'      => $only_wp_city,
			'location_region'    => sevensports_city_to_region_slug( $only_wp_city ),
			'location_latitude'  => get_post_meta( $p->ID, 'location_latitude', true ),
			'location_longitude' => get_post_meta( $p->ID, 'location_longitude', true ),
			'age_range'          => get_post_meta( $p->ID, 'age_range', true ),
			'program_type'       => get_post_meta( $p->ID, 'program_type', true ),
			'season'             => get_post_meta( $p->ID, 'season', true ),
			'schedule'           => get_post_meta( $p->ID, 'schedule', true ),
			'price'              => get_post_meta( $p->ID, 'price', true ),
			'inscription_link'   => get_post_meta( $p->ID, 'inscription_link', true ),
			'trial_link'         => get_post_meta( $p->ID, 'trial_link', true ),
		);
	}
}
$program_count  = count( $programs );
$unique_sports  = array();
$unique_seasons = array();
$cities_by_region = array();
foreach ( $programs as $p_item ) {
	if ( ! empty( $p_item['sport_type'] ) && ! in_array( $p_item['sport_type'], $unique_sports, true ) ) {
		$unique_sports[] = $p_item['sport_type'];
	}
	if ( ! empty( $p_item['season'] ) && ! in_array( $p_item['season'], $unique_seasons, true ) ) {
		$unique_seasons[] = $p_item['season'];
	}
	$p_city   = isset( $p_item['location_city'] ) ? trim( (string) $p_item['location_city'] ) : '';
	$p_region = isset( $p_item['location_region'] ) ? trim( (string) $p_item['location_region'] ) : '';
	if ( $p_city !== '' && $p_region !== '' ) {
		if ( ! isset( $cities_by_region[ $p_region ] ) ) {
			$cities_by_region[ $p_region ] = array();
		}
		if ( ! in_array( $p_city, $cities_by_region[ $p_region ], true ) ) {
			$cities_by_region[ $p_region ][] = $p_city;
		}
	}
}
sort( $unique_sports );
sort( $unique_seasons );
foreach ( $cities_by_region as &$cbr_cities ) {
	sort( $cbr_cities );
}
unset( $cbr_cities );
$filter_region_labels = array(
	'montreal_monteregie' => 'Montréal / Montérégie',
	'outaouais'           => 'Outaouais',
	'estrie'              => 'Estrie',
);
$hero_region_aliases = array();
foreach ( $filter_region_labels as $slug => $label ) {
	$lk = function_exists( 'mb_strtolower' )
		? mb_strtolower( $label, 'UTF-8' )
		: strtolower( $label );
	$hero_region_aliases[ $lk ] = $slug;
}
$hero_region_aliases['montreal']              = 'montreal_monteregie';
$hero_region_aliases['montréal']             = 'montreal_monteregie';
$hero_region_aliases['monteregie']           = 'montreal_monteregie';
$hero_region_aliases['montérégie']            = 'montreal_monteregie';
$hero_region_aliases['montreal / monteregie'] = 'montreal_monteregie';
$hero_region_aliases['national capital']      = 'outaouais';
$hero_region_aliases['gatineau']              = 'outaouais';
$hero_region_aliases['ouatouais']              = 'outaouais';
$hero_region_aliases['estrie']                = 'estrie';
$sport_labels = array(
    'soccer'      => 'Soccer',
    'dek_hockey'  => 'Dek Hockey',
    'multi_sport' => 'Multi-Sport',
);
$programs_js = array();
foreach ( $programs as $pi => $p_item ) {
    $programs_js[] = array(
        'index' => $pi,
        'name'  => $p_item['program_name'],
        'lat'   => $p_item['location_latitude'],
        'lng'   => $p_item['location_longitude'],
        'sport' => $p_item['sport_type'],
    );
}
?>

<!-- Filter Section -->
<?php
$reg_section_bg = function_exists( 'sevensports_section_wireframe_bg_style' ) ? sevensports_section_wireframe_bg_style( get_queried_object_id() ?: get_the_ID(), 'registration_section_bg_image' ) : '';
?>
<section class="section-wireframe"<?php echo $reg_section_bg ? ' style="' . $reg_section_bg . '"' : ''; ?>>
    <div class="container">
        <div class="filter-section">
            <div class="row g-3 align-items-end">
                <div class="col-md">
                    <label class="form-label fw-bold small">Region / City</label>
                    <div class="filter-checkbox-dropdown">
                        <button type="button" class="filter-dropdown-toggle" data-filter="filter-region">All Areas</button>
                        <div class="filter-dropdown-menu">
                            <?php foreach ( $filter_region_labels as $region_slug => $region_label ) : ?>
                                <label class="filter-checkbox-label filter-region-parent" style="font-weight:600;">
                                    <input type="checkbox" class="filter-checkbox filter-region-cb" name="filter-region" value="<?php echo esc_attr( $region_slug ); ?>">
                                    <?php echo esc_html( $region_label ); ?>
                                </label>
                                <?php if ( ! empty( $cities_by_region[ $region_slug ] ) ) : ?>
                                    <div class="filter-city-group" data-region="<?php echo esc_attr( $region_slug ); ?>" style="display:none; padding-left:18px; border-left:2px solid #e0e0e0; margin-left:20px;">
                                        <?php foreach ( $cities_by_region[ $region_slug ] as $city_name ) : ?>
                                            <label class="filter-checkbox-label">
                                                <input type="checkbox" class="filter-checkbox filter-city-cb" name="filter-city" value="<?php echo esc_attr( $city_name ); ?>" data-region="<?php echo esc_attr( $region_slug ); ?>">
                                                <?php echo esc_html( $city_name ); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md">
                    <label class="form-label fw-bold small" for="filter-child-age">Child Age</label>
                    <input type="number" class="form-control" id="filter-child-age" min="1" max="18" step="1" placeholder="e.g. 6">
                </div>
                <div class="col-md">
                    <label class="form-label fw-bold small">Sport</label>
                    <div class="filter-checkbox-dropdown">
                        <button type="button" class="filter-dropdown-toggle" data-filter="filter-sport">All Sports</button>
                        <div class="filter-dropdown-menu">
                            <?php foreach ( $unique_sports as $sport ) : ?>
                                <label class="filter-checkbox-label">
                                    <input type="checkbox" class="filter-checkbox" name="filter-sport" value="<?php echo esc_attr( $sport ); ?>">
                                    <?php echo esc_html( isset( $sport_labels[ $sport ] ) ? $sport_labels[ $sport ] : $sport ); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md">
                    <label class="form-label fw-bold small">Season</label>
                    <div class="filter-checkbox-dropdown">
                        <button type="button" class="filter-dropdown-toggle" data-filter="filter-season">All Seasons</button>
                        <div class="filter-dropdown-menu">
                            <?php foreach ( $unique_seasons as $season ) : ?>
                                <label class="filter-checkbox-label">
                                    <input type="checkbox" class="filter-checkbox" name="filter-season" value="<?php echo esc_attr( $season ); ?>">
                                    <?php echo esc_html( $season ); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md">
                    <label class="form-label fw-bold small">Max Distance</label>
                    <select class="form-select" id="filter-distance">
                        <option value="0">Any Distance</option>
                        <option value="5">Within 5 km</option>
                        <option value="10">Within 10 km</option>
                        <option value="25">Within 25 km</option>
                        <option value="50">Within 50 km</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map and Programs Section -->
<section class="section-wireframe"<?php echo ! empty( $reg_section_bg ) ? ' style="' . $reg_section_bg . '"' : ''; ?>>
    <div class="container">
        <div class="row g-4">
            <!-- Map Column -->
            <div class="col-lg-5">
                <div class="map-wrapper">
                    <div id="programMap"></div>
                    <!-- Map Legend (top right on map) -->
                    <div class="map-legend">
                        <h6 class="fw-bold mb-1">LEGEND</h6>
                        <div class="legend-item">
                            <span class="legend-dot soccer"></span>
                            <span>Soccer</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot dek-hockey"></span>
                            <span>Dek Hockey</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot multi-sport"></span>
                            <span>Multi-Sport</span>
                        </div>
                        <div class="map-legend-note-card">
                            <div class="map-legend-note-back"></div>
                            <div class="map-legend-note-front">
                                <strong>📍 Multiple Programs?</strong><br>
                                Some locations (like Pierrefonds) offer multiple program types at the same facility. Click on the location marker or result card to see all programs available at that site.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Programs List Column -->
            <div class="col-lg-7">
                
                <div class="mb-4">
                    <h2 class="h3 fw-bold mb-1"><?php echo $programs_title ? esc_html($programs_title) : 'Available Programs'; ?></h2>
                    <p class="text-danger fw-semibold" id="programs-count"><?php echo $program_count; ?> programs found</p>
                </div>
                
                <div class="programs-scroll">
                <?php
                if ( !empty($programs) && is_array($programs) ):
                    foreach ( $programs as $program_index => $program ):
                        $sport_type = isset( $program['sport_type'] ) ? trim( (string) $program['sport_type'] ) : '';
                        $card_sport_class = '';
                        if ( $sport_type === 'soccer' ) {
                            $card_sport_class = ' program-card-soccer';
                        } elseif ( $sport_type === 'dek_hockey' ) {
                            $card_sport_class = ' program-card-dek-hockey';
                        } elseif ( $sport_type === 'multi_sport' ) {
                            $card_sport_class = ' program-card-multi-sport';
                        }
                ?>
                    <div class="program-card<?php echo esc_attr( $card_sport_class ); ?>"
                         data-index="<?php echo (int) $program_index; ?>"
                         data-city="<?php echo esc_attr( $program['location_city'] ?? '' ); ?>"
                         data-region="<?php echo esc_attr( $program['location_region'] ?? '' ); ?>"
                         data-sport="<?php echo esc_attr( $program['sport_type'] ?? '' ); ?>"
                         data-age="<?php echo esc_attr( $program['age_range'] ?? '' ); ?>"
                         data-season="<?php echo esc_attr( $program['season'] ?? '' ); ?>"
                         data-lat="<?php echo esc_attr( $program['location_latitude'] ?? '' ); ?>"
                         data-lng="<?php echo esc_attr( $program['location_longitude'] ?? '' ); ?>">
                        <?php if ( ! empty( $program['program_image_url'] ) ) : ?>
                        <div class="program-card-image">
                            <img src="<?php echo esc_url( $program['program_image_url'] ); ?>" alt="" loading="lazy" decoding="async">
                        </div>
                        <?php endif; ?>
                        <?php
                        $sport_badge = '';
                        if ( ! empty( $program['sport_label'] ) ) {
                            $sport_badge = $program['sport_label'];
                        } elseif ( ! empty( $program['sport_type'] ) ) {
                            $sport_badge = isset( $sport_labels[ $program['sport_type'] ] ) ? $sport_labels[ $program['sport_type'] ] : $program['sport_type'];
                        }
                        ?>
                        <?php if ( $sport_badge !== '' ) : ?>
                            <span class="program-title-badge">
                                <?php echo esc_html( $sport_badge ); ?>
                            </span>
                        <?php endif; ?>
                        
                        <div class="program-details">
                            <div class="program-details-heading"><?php echo esc_html( isset( $program['program_name'] ) && $program['program_name'] !== '' ? $program['program_name'] : ( isset( $program['location_heading'] ) && $program['location_heading'] !== '' ? $program['location_heading'] : 'Program' ) ); ?></div>
                            <?php
                            $loc_heading_display = isset( $program['location_heading'] ) ? trim( (string) $program['location_heading'] ) : '';
                            $loc_addr_display    = isset( $program['location_address'] ) ? trim( (string) $program['location_address'] ) : '';
                            if ( $loc_heading_display !== '' || $loc_addr_display !== '' ) :
                            ?>
                                <div class="program-details-location">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 5px; vertical-align: middle;">
                                        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                                    </svg>
                                    <?php
                                    if ( $loc_heading_display !== '' && $loc_addr_display !== '' ) {
                                        echo '<span class="program-details-location-heading">' . esc_html( $loc_heading_display ) . '</span> · ' . esc_html( $loc_addr_display );
                                    } elseif ( $loc_addr_display !== '' ) {
                                        echo esc_html( $loc_addr_display );
                                    } else {
                                        echo esc_html( $loc_heading_display );
                                    }
                                    ?>
                                    <span class="program-distance" style="display:none;"> • <span class="program-distance-value"></span></span>
                                </div>
                            <?php endif; ?>
                            <div class="program-details-meta">
                                <?php if ( isset($program['age_range']) && $program['age_range'] ): ?>
                                    <span class="program-detail-item"><?php echo esc_html($program['age_range']); ?></span>
                                <?php endif; ?>
                                <?php if ( isset($program['program_type']) && $program['program_type'] ): ?>
                                    <span class="program-detail-item"><?php echo esc_html($program['program_type']); ?></span>
                                <?php endif; ?>
                                <?php if ( isset($program['schedule']) && $program['schedule'] ): ?>
                                    <span class="program-detail-item"><?php echo esc_html($program['schedule']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="program-price-buttons-row">
                            <?php if ( isset($program['price']) && $program['price'] ): ?>
                                <strong class="program-price mb-0"><?php echo esc_html($program['price']); ?></strong>
                            <?php endif; ?>
                            <div class="program-card-buttons">
                                <?php if ( isset($program['inscription_link']) && $program['inscription_link'] ): ?>
                                    <a href="<?php echo esc_url($program['inscription_link']); ?>"
                                       class="btn btn-danger inscription-btn">
                                        Inscription
                                    </a>
                                <?php endif; ?>
                                <?php if ( isset($program['trial_link']) && $program['trial_link'] ): ?>
                                    <a href="<?php echo esc_url($program['trial_link']); ?>"
                                       class="btn btn-danger">
                                        Essais gratuits
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php
                    endforeach;
                else:
                ?>
                    <div class="wireframe-box">
                        <?php if ( isset( $qidigo_rows ) && is_array( $qidigo_rows ) ) : ?>
                            No programs in <code>programs.json</code>. Run the Qidigo scraper or add rows to the JSON file.
                        <?php else : ?>
                            ADD PROGRAMS IN WORDPRESS ADMIN<br>
                            (Programs &amp; Locations Section)
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                </div><!-- /.programs-scroll -->
            </div>
        </div>
    </div>
</section>

<!-- Bottom CTA Section -->
<section class="section-wireframe cta-section-dark" style="background-color: #000; color: #fff;">
    <div class="cta-wave">
        <svg viewBox="0 0 1440 60" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0,0 L0,30 Q360,60 720,30 Q1080,0 1440,30 L1440,60 L0,60 Z"/>
        </svg>
    </div>
    <div class="container text-center">
        <div class="d-flex flex-wrap cta-buttons-wrap justify-content-center">
            <?php 
            $cta1_text = rwmb_meta( 'registration_cta_button_1_text' );
            $cta1_link = rwmb_meta( 'registration_cta_button_1_link' );
            if ( $cta1_text && $cta1_link ):
            ?>
                <a href="<?php echo esc_url($cta1_link); ?>"
                   class="btn btn-danger btn-lg" style="min-width: 240px; padding: 18px 120px; border-radius: 14px; font-family: 'Gill Sans', 'Gill Sans MT', Calibri, sans-serif; font-size: 1.3rem; font-weight: 600;">
                    <?php echo esc_html($cta1_text); ?>
                </a>
            <?php else: ?>
                <div class="wireframe-box" style="min-width: 250px; background: #333; border-color: #666;">BUTTON 1</div>
            <?php endif; ?>
            
            <?php 
            $cta2_text = rwmb_meta( 'registration_cta_button_2_text' );
            $cta2_link = rwmb_meta( 'registration_cta_button_2_link' );
            if ( $cta2_text && $cta2_link ):
            ?>
                <a href="<?php echo esc_url($cta2_link); ?>"
                   class="btn btn-lg cta-btn-white" style="min-width: 240px; padding: 18px 120px; border-radius: 14px; font-family: 'Gill Sans', 'Gill Sans MT', Calibri, sans-serif; font-size: 1.3rem; font-weight: 600;">
                    <?php echo esc_html($cta2_text); ?>
                </a>
            <?php else: ?>
                <div class="wireframe-box" style="min-width: 250px; background: #333; border-color: #666;">BUTTON 2</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php get_footer(); ?>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" 
        crossorigin=""></script>

<script>
(function () {
    var programsData = <?php echo wp_json_encode( $programs_js ); ?>;
    var filterRegionSlugs = <?php echo wp_json_encode( array_keys( $filter_region_labels ) ); ?>;
    var regionHeroAliases = <?php echo wp_json_encode( $hero_region_aliases ); ?>;
    var regionBounds = {
        montreal_monteregie: [[45.0544, -74.1756], [45.6822, -72.9373]],
        outaouais:           [[45.3973, -75.8040], [45.5558, -75.5073]],
        estrie:              [[45.2134, -72.9678], [45.4593, -71.9319]]
    };

    var userLat = null;
    var userLng = null;
    var markers = [];
    var map;
    var allMarkerBounds = [];

    // Haversine distance in km
    function haversine(lat1, lon1, lat2, lon2) {
        var R = 6371;
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLon = (lon2 - lon1) * Math.PI / 180;
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function updateDistances() {
        if (userLat === null || userLng === null) return;
        document.querySelectorAll('.program-card').forEach(function (card) {
            var lat = parseFloat(card.dataset.lat);
            var lng = parseFloat(card.dataset.lng);
            var distWrap = card.querySelector('.program-distance');
            var distVal  = card.querySelector('.program-distance-value');
            if (distWrap && distVal && lat && lng) {
                var dist = haversine(userLat, userLng, lat, lng);
                distVal.textContent = dist.toFixed(1) + ' km away';
                distWrap.style.display = 'inline';
            }
        });
    }

    function sortCardsByDistance() {
        if (userLat === null || userLng === null) return;
        var container = document.querySelector('.programs-scroll');
        if (!container) return;
        var cards = Array.from(container.querySelectorAll('.program-card'));
        cards.sort(function (a, b) {
            var aLat = parseFloat(a.dataset.lat);
            var aLng = parseFloat(a.dataset.lng);
            var bLat = parseFloat(b.dataset.lat);
            var bLng = parseFloat(b.dataset.lng);
            var aHas = aLat && aLng;
            var bHas = bLat && bLng;
            if (!aHas && !bHas) return 0;
            if (!aHas) return 1;
            if (!bHas) return -1;
            return haversine(userLat, userLng, aLat, aLng) - haversine(userLat, userLng, bLat, bLng);
        });
        cards.forEach(function (card) { container.appendChild(card); });
    }

    function getCheckedValues(name) {
        var values = [];
        document.querySelectorAll('input[name="' + name + '"]:checked').forEach(function(cb) {
            values.push(cb.value.toLowerCase());
        });
        return values;
    }

    var dropdownDefaults = {
        'filter-region': 'All Areas',
        'filter-sport':  'All Sports',
        'filter-season': 'All Seasons'
    };

    function parseAgeNumbers(ageText) {
        var nums = String(ageText || '').match(/\d+(?:[.,]\d+)?/g);
        if (!nums) return [];
        return nums.map(function(n) {
            return parseFloat(String(n).replace(',', '.'));
        }).filter(function(n) { return !Number.isNaN(n); });
    }

    function matchesChildAge(ageText, childAge) {
        if (childAge === null) return true;
        var txt = String(ageText || '').toLowerCase();
        if (!txt) return false;
        var nums = parseAgeNumbers(txt);
        if (!nums.length) return true;
        var isMonths = /(mois|month|months)\b/i.test(txt) || /\b\d+(?:[.,]\d+)?\s*m\b/i.test(txt);
        if (isMonths) {
            if (nums.length >= 2) {
                var minMonths = Math.min(nums[0], nums[1]);
                var maxMonths = Math.max(nums[0], nums[1]);
                var ageStartMonths = childAge * 12;
                var ageEndMonths = ((childAge + 1) * 12) - 1;
                return maxMonths >= ageStartMonths && minMonths <= ageEndMonths;
            }
            return (childAge * 12) >= nums[0];
        }
        if (/[+]|(et\s*plus)|(and\s*older)|(ou\s*plus)/i.test(txt)) {
            return childAge >= nums[0];
        }
        if (nums.length >= 2) {
            var min = Math.min(nums[0], nums[1]);
            var max = Math.max(nums[0], nums[1]);
            return childAge >= min && childAge <= max;
        }
        return childAge >= nums[0];
    }

    function updateDropdownLabel(filterName) {
        var first = document.querySelector('input[name="' + filterName + '"]');
        if (!first) return;
        var toggle = first.closest('.filter-checkbox-dropdown').querySelector('.filter-dropdown-toggle');
        if (filterName === 'filter-region') {
            var checkedRegions = document.querySelectorAll('input[name="filter-region"]:checked');
            var checkedCities  = document.querySelectorAll('input[name="filter-city"]:checked');
            if (checkedRegions.length === 0) {
                toggle.textContent = 'All Areas';
            } else if (checkedCities.length > 0) {
                if (checkedCities.length === 1) {
                    toggle.textContent = checkedCities[0].parentElement.textContent.trim();
                } else {
                    toggle.textContent = checkedCities.length + ' cities selected';
                }
            } else if (checkedRegions.length === 1) {
                toggle.textContent = checkedRegions[0].parentElement.textContent.trim();
            } else {
                toggle.textContent = checkedRegions.length + ' regions selected';
            }
            return;
        }
        var checked = document.querySelectorAll('input[name="' + filterName + '"]:checked');
        if (checked.length === 0) {
            toggle.textContent = dropdownDefaults[filterName] || 'All';
        } else if (checked.length === 1) {
            toggle.textContent = checked[0].parentElement.textContent.trim();
        } else {
            toggle.textContent = checked.length + ' selected';
        }
    }

    function applyFilters() {
        var filterRegions = getCheckedValues('filter-region');
        var filterCities  = [];
        document.querySelectorAll('input[name="filter-city"]:checked').forEach(function(cb) {
            filterCities.push(cb.value);
        });
        var filterSports  = getCheckedValues('filter-sport');
        var filterSeasons = getCheckedValues('filter-season');
        var filterDist    = parseFloat(document.getElementById('filter-distance').value) || 0;
        var childAgeRaw   = document.getElementById('filter-child-age').value;
        var childAge      = childAgeRaw === '' ? null : parseFloat(childAgeRaw);
        if (Number.isNaN(childAge)) childAge = null;

        var visibleCount = 0;

        document.querySelectorAll('.program-card').forEach(function (card) {
            var idx    = parseInt(card.dataset.index, 10);
            var region = (card.dataset.region || '').toLowerCase();
            var city   = (card.dataset.city || '');
            var sport  = (card.dataset.sport  || '').toLowerCase();
            var age    = (card.dataset.age    || '').toLowerCase();
            var season = (card.dataset.season || '').toLowerCase();
            var lat    = parseFloat(card.dataset.lat);
            var lng    = parseFloat(card.dataset.lng);

            var show = true;

            if (filterCities.length) {
                if (filterCities.indexOf(city) === -1) show = false;
            } else if (filterRegions.length) {
                if (filterRegions.indexOf(region) === -1) show = false;
            }
            if (filterSports.length  && filterSports.indexOf(sport)  === -1) show = false;
            if (!matchesChildAge(age, childAge)) show = false;
            if (filterSeasons.length && filterSeasons.indexOf(season) === -1) show = false;

            if (filterDist > 0) {
                if (userLat === null || userLng === null) {
                    // Distance filter requested but no location — leave cards visible
                } else if (!lat || !lng || haversine(userLat, userLng, lat, lng) > filterDist) {
                    show = false;
                }
            }

            card.style.display = show ? '' : 'none';
            if (show) visibleCount++;

            if (markers[idx]) {
                if (show) {
                    if (!map.hasLayer(markers[idx])) markers[idx].addTo(map);
                } else {
                    if (map.hasLayer(markers[idx])) map.removeLayer(markers[idx]);
                }
            }
        });

        // Zoom map to selected region bounds
        if (filterRegions.length === 1 && filterCities.length === 0 && regionBounds[filterRegions[0]]) {
            var b = regionBounds[filterRegions[0]];
            map.fitBounds(b, { padding: [30, 30], maxZoom: 13 });
        } else if (filterRegions.length === 0 && filterCities.length === 0 && allMarkerBounds.length > 0) {
            map.fitBounds(allMarkerBounds, { padding: [30, 30], maxZoom: 13 });
        }

        var countEl = document.getElementById('programs-count');
        if (countEl) countEl.textContent = visibleCount + ' programs found';
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Map settings
        <?php
        $map_lat  = rwmb_meta( 'map_center_latitude' )  ?: '45.5017';
        $map_lng  = rwmb_meta( 'map_center_longitude' ) ?: '-73.5673';
        $map_zoom = rwmb_meta( 'map_zoom_level' )        ?: 11;
        ?>
        map = L.map('programMap').setView([<?php echo (float) $map_lat; ?>, <?php echo (float) $map_lng; ?>], <?php echo (int) $map_zoom; ?>);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Sport-specific marker icons
        var sportColors = {
            soccer:      '#dc3545',
            dek_hockey:  '#0d6efd',
            multi_sport: '#ffc107'
        };
        function makeIcon(color, active) {
            var size   = active ? 35 : 25;
            var anchor = active ? 17 : 12;
            var shadow = active ? ';box-shadow:0 0 0 3px ' + color : '';
            return L.divIcon({
                className: 'custom-marker',
                html: '<div style="background-color:' + color + ';width:' + size + 'px;height:' + size + 'px;border-radius:50%;border:3px solid white' + shadow + ';"></div>',
                iconSize:   [size, size],
                iconAnchor: [anchor, anchor]
            });
        }

        function scrollToCard(index) {
            var card      = document.querySelector('.program-card[data-index="' + index + '"]');
            var container = document.querySelector('.programs-scroll');
            if (!card || !container) return;
            document.querySelectorAll('.program-card').forEach(function (c) { c.classList.remove('program-card-active'); });
            card.classList.add('program-card-active');
            container.scrollTo({ top: card.offsetTop - container.offsetTop, behavior: 'smooth' });
        }

        function highlightMarker(index) {
            programsData.forEach(function (p) {
                if (markers[p.index]) {
                    markers[p.index].setIcon(makeIcon(sportColors[p.sport] || '#dc3545', false));
                }
            });
            if (markers[index]) {
                var sport = programsData[index] ? programsData[index].sport : 'soccer';
                markers[index].setIcon(makeIcon(sportColors[sport] || '#dc3545', true));
                map.panTo(markers[index].getLatLng());
            }
        }

        // Place markers
        allMarkerBounds = [];
        programsData.forEach(function (p) {
            if (p.lat && p.lng) {
                var lat = parseFloat(p.lat);
                var lng = parseFloat(p.lng);
                var icon   = makeIcon(sportColors[p.sport] || '#dc3545', false);
                var marker = L.marker([lat, lng], { icon: icon }).addTo(map);
                marker.on('click', function () { scrollToCard(p.index); });
                markers[p.index] = marker;
                allMarkerBounds.push([lat, lng]);
            } else {
                markers[p.index] = null;
            }
        });
        if (allMarkerBounds.length > 0) {
            map.fitBounds(allMarkerBounds, { padding: [30, 30], maxZoom: 13 });
        }

        // Card click → highlight marker (ignore clicks on buttons/links)
        document.querySelectorAll('.program-card').forEach(function (card) {
            card.style.cursor = 'pointer';
            card.addEventListener('click', function (e) {
                if (e.target.closest('a, button')) return;
                var idx = parseInt(card.dataset.index, 10);
                scrollToCard(idx);
                highlightMarker(idx);
            });
        });


        // Dropdown-checkbox toggle
        document.querySelectorAll('.filter-dropdown-toggle').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var menu = btn.nextElementSibling;
                var wasOpen = menu.classList.contains('show');
                document.querySelectorAll('.filter-dropdown-menu.show').forEach(function(m) { m.classList.remove('show'); });
                if (!wasOpen) menu.classList.add('show');
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.filter-checkbox-dropdown')) {
                document.querySelectorAll('.filter-dropdown-menu.show').forEach(function(m) { m.classList.remove('show'); });
            }
        });

        // Region checkbox → show/hide nested city checkboxes
        document.querySelectorAll('.filter-region-cb').forEach(function(regionCb) {
            regionCb.addEventListener('change', function() {
                var regionVal = this.value;
                var cityGroup = document.querySelector('.filter-city-group[data-region="' + regionVal + '"]');
                if (cityGroup) {
                    if (this.checked) {
                        cityGroup.style.display = 'block';
                    } else {
                        cityGroup.style.display = 'none';
                        cityGroup.querySelectorAll('.filter-city-cb').forEach(function(ccb) {
                            ccb.checked = false;
                        });
                    }
                }
                updateDropdownLabel('filter-region');
                applyFilters();
            });
        });

        // City checkbox changes → update label + filter
        document.querySelectorAll('.filter-city-cb').forEach(function(cb) {
            cb.addEventListener('change', function() {
                updateDropdownLabel('filter-region');
                applyFilters();
            });
        });

        // Auto-apply on checkbox change (sport, season)
        document.querySelectorAll('.filter-checkbox:not(.filter-region-cb):not(.filter-city-cb)').forEach(function(cb) {
            cb.addEventListener('change', function() {
                updateDropdownLabel(this.name);
                applyFilters();
            });
        });

        // Auto-apply on distance change
        var distSelect = document.getElementById('filter-distance');
        if (distSelect) {
            distSelect.addEventListener('change', applyFilters);
        }
        var ageInput = document.getElementById('filter-child-age');
        if (ageInput) {
            ageInput.addEventListener('input', applyFilters);
        }

        // Request user location for distance features
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                userLat = pos.coords.latitude;
                userLng = pos.coords.longitude;
                sortCardsByDistance();
                updateDistances();
                applyFilters();
            });
        }
    });

    function filterByRegion(regionName) {
        var raw = String(regionName || '').toLowerCase().trim();
        var slug = regionHeroAliases[raw] || raw;
        if (filterRegionSlugs.indexOf(slug) === -1) {
            slug = raw.replace(/\s+/g, '_');
        }
        document.querySelectorAll('input[name="filter-region"]').forEach(function(cb) {
            cb.checked = (cb.value === slug);
            // Show nested cities for selected region
            var cityGroup = document.querySelector('.filter-city-group[data-region="' + cb.value + '"]');
            if (cityGroup) {
                cityGroup.style.display = cb.checked ? 'block' : 'none';
                if (!cb.checked) {
                    cityGroup.querySelectorAll('.filter-city-cb').forEach(function(ccb) { ccb.checked = false; });
                }
            }
        });
        // Clear any city selections so the full region shows
        document.querySelectorAll('.filter-city-cb').forEach(function(ccb) { ccb.checked = false; });
        updateDropdownLabel('filter-region');
        applyFilters();

        var mapEl = document.getElementById('programMap');
        if (mapEl) {
            var offset = 96;
            var y = mapEl.getBoundingClientRect().top + window.pageYOffset - offset;
            if (Math.abs(y - window.pageYOffset) > 32) {
                window.scrollTo({ top: Math.max(0, y), behavior: 'smooth' });
            }
        }

        document.querySelectorAll('.region-button').forEach(function(btn) {
            btn.classList.remove('active');
            var btxt = btn.textContent.trim().toLowerCase();
            if (btxt === raw || (regionHeroAliases[btxt] && regionHeroAliases[btxt] === slug)) {
                btn.classList.add('active');
            }
        });
    }

    // expose filterByRegion for the region buttons
    window.filterByRegion = filterByRegion;
})();
</script>

</body>
</html>