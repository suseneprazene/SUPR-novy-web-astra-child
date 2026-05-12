<?php
/*
Theme Name: Astra Child
Description: Astra child theme – moderní černobílý design.
Author: tvé_jméno
Template: astra
Version: 1.0.0
*/

// Načtení stylů child + parent theme
function astra_child_enqueue_styles() {
    wp_enqueue_style( 'astra-parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'astra-child-style', get_stylesheet_uri(), array('astra-parent-style'), '1.0' );
}
add_action( 'wp_enqueue_scripts', 'astra_child_enqueue_styles' );

// Cabinet Grotesk font
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'cabinet-grotesk-font',
        'https://api.fontshare.com/v2/css?f[]=cabinet-grotesk@700,400&display=swap',
        false,
        null
    );
});

/**
 * Přebití Astra výchozích barev (modrá → černá) pomocí inline stylu.
 * Inline styl se načte PO všech ostatních stylech, takže má vyšší specificitu.
 * Toto je nejspolehlivější způsob, jak přebít Astra customizer hodnoty.
 */
add_action( 'wp_enqueue_scripts', function() {
    // Ujistíme se, že child styl je zaregistrovaný, pak přidáme inline CSS
    wp_add_inline_style( 'astra-child-style', '

        /* === Přebití Astra modré barvy odkazů a ikon === */

        /* Globální barva odkazů */
        a,
        a:visited {
            color: #111 !important;
        }
        a:hover,
        a:focus {
            color: #000 !important;
            text-decoration: none !important;
        }

        /* Astra vlastní CSS proměnné (pokud je téma používá) */
        :root {
            --ast-global-color-0: #111111 !important;
            --ast-global-color-1: #111111 !important;
        }

        /* Ikony košíku a účtu */
        .ast-cart-menu-wrap a,
        .ast-header-woo-cart a,
        .ast-header-account a,
        .ast-masthead-custom-menu-items a {
            color: #111 !important;
        }

        /* SVG ikony – fill */
        .ast-cart-menu-wrap svg,
        .ast-header-woo-cart svg,
        .ast-header-account svg,
        .ast-masthead-custom-menu-items svg {
            fill: #111 !important;
            color: #111 !important;
        }

        /* Badge počtu položek v košíku */
        .ast-cart-menu-wrap .count {
            background-color: #111 !important;
            color: #fff !important;
            border-color: #111 !important;
        }

        /* Menu položky */
        .main-navigation a,
        .main-header-menu a,
        #ast-fixed-header .main-header-menu a,
        .ast-main-header-bar-navigation a {
            color: #111 !important;
        }

        /* Woo: ceny, linky, tabs */
        .woocommerce a,
        .woocommerce-page a,
        .woocommerce ul.products li.product a,
        .woocommerce .woocommerce-breadcrumb a {
            color: #111 !important;
        }

        /* Výjimka – tlačítka v gridu i summary musí mít bílý text */
        .woocommerce ul.products li.product a.button,
        .woocommerce ul.products li.product .astra-shop-summary-wrap a.button,
        .woocommerce-page ul.products li.product a.button,
        .astra-shop-summary-wrap a.button,
        .astra-shop-summary-wrap a.single_add_to_cart_button,
        .astra-shop-summary-wrap a.add_to_cart_button {
            color: #fff !important;
            background-color: #111 !important;
        }

        /* Výjimka – košík ikonka (.ast-on-card-button) průhledná */
        .astra-shop-thumbnail-wrap a.ast-on-card-button {
            color: #111 !important;
            background: transparent !important;
            background-color: transparent !important;
        }

        /* Out-of-stock text */
        .ast-shop-product-out-of-stock {
            color: #111 !important;
        }

    ');
}, 99 ); // priorita 99 – načte se po Astra stylech

/**
 * JS: Přesune tlačítko "Odstranit položku" (.wc-block-cart-item__remove-link)
 * do buňky s cenou (.wc-block-cart-item__total) a zobrazí ho jako malé X.
 * Používáme MutationObserver, protože WC Blocks renderuje přes React.
 */
add_action( 'wp_footer', function() { ?>
<script>
(function() {
  function moveRemoveLinks() {
    document.querySelectorAll('.wc-block-cart-item__total').forEach(function(totalCell) {
      var row = totalCell.closest('tr, .wc-block-cart-item');
      if (!row) return;
      var removeLink = row.querySelector('.wc-block-cart-item__remove-link');
      if (!removeLink) return;
      if (totalCell.querySelector('.wc-block-cart-item__remove-link')) return;

      removeLink.classList.add('remove-x-btn');
      removeLink.innerHTML = '&times;';
      removeLink.setAttribute('title', 'Odstranit položku');
      totalCell.style.position = 'relative';
      totalCell.appendChild(removeLink);
    });
  }

  var observer = new MutationObserver(moveRemoveLinks);
  observer.observe(document.body, { childList: true, subtree: true });
  document.addEventListener('DOMContentLoaded', moveRemoveLinks);
})();
</script>
<?php });

/**
 * JS: Fix mobilního menu – Astra občas neregistruje klik mimo menu jako zavření.
 * Klik mimo .main-header-bar-navigation zavře menu manuálně.
 * Také přesune mini-cart do pravé sekce headeru na mobilu pokud tam není.
 */
add_action( 'wp_footer', function() { ?>
<script>
(function() {
  document.addEventListener('DOMContentLoaded', function() {

    // ── Fix zavírání mobilního menu ──────────────────────────────────────
    // Astra toggle funguje přes aria-expanded na .menu-toggle
    // Zavřeme menu při kliknutí mimo header nebo na odkaz v menu

    function closeMobileMenu() {
      var toggles = document.querySelectorAll('.menu-toggle[aria-expanded="true"]');
      toggles.forEach(function(toggle) {
        toggle.click();
      });
    }

    // Klik na odkaz v mobilním menu → zavřít
    document.addEventListener('click', function(e) {
      var nav = document.querySelector('.main-header-bar-navigation, #ast-fixed-header .main-header-bar-navigation');
      var toggle = document.querySelector('.menu-toggle');
      if (!nav || !toggle) return;

      // Klik byl mimo header → zavřít
      var header = document.querySelector('.main-header-bar, #masthead');
      if (header && !header.contains(e.target)) {
        if (toggle.getAttribute('aria-expanded') === 'true') {
          closeMobileMenu();
        }
        return;
      }

      // Klik na odkaz uvnitř menu → zavřít
      if (e.target.tagName === 'A' && nav.contains(e.target)) {
        setTimeout(closeMobileMenu, 100);
      }
    });

    // Klávesa Escape → zavřít menu
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeMobileMenu();
    });

    // ── Mini-cart v pravé sekci headeru na mobilu ────────────────────────
    // Zkontrolujeme, jestli je mini-cart uvnitř .site-header-primary-section-right
    // Pokud ne, zkusíme ho přesunout (záleží na Header Builder konfiguraci)
    function ensureCartInRightSection() {
      var rightSection = document.querySelector('.site-header-primary-section-right');
      if (!rightSection) return;

      // Pokud mini-cart nebo account ještě není v pravé sekci, zkusíme přidat
      // (funguje jen pokud jsou to siblové ve stejném flex containeru)
      var miniCart = document.querySelector('.ast-builder-layout-element .wc-block-mini-cart, .ast-builder-layout-element .ast-site-header-cart');
      var account  = document.querySelector('.ast-builder-layout-element.ast-header-account');

      if (miniCart) {
        var cartEl = miniCart.closest('.ast-builder-layout-element');
        if (cartEl && !rightSection.contains(cartEl)) {
          // Je ve stejném parent containeru? Pak přesuneme.
          if (cartEl.parentElement === rightSection.parentElement) {
            rightSection.insertBefore(cartEl, rightSection.querySelector('.ast-mobile-menu-trigger') || null);
          }
        }
      }
    }

    // Spustíme po načtení a po resize (breakpoint přechod)
    ensureCartInRightSection();
    window.addEventListener('resize', ensureCartInRightSection);
  });
})();
</script>
<?php });

// … další PHP úpravy (widgety, WooCommerce aj.) sem ↓

add_filter( 'woocommerce_get_availability_text', function( $text, $product ) {
    if ( ! $product->is_in_stock() ) {
        return 'Momentálně vyprodáno';
    }
    return $text;
}, 10, 2 );

add_filter( 'woocommerce_sale_flash', function( $html ) {
    return '<span class="onsale ast-on-card-button ast-onsale-card">Výhodněji</span>';
} );

// Překlad Astra labelů (Výprodej!, Nedostupné) přes gettext
add_filter( 'gettext', function( $translated, $text, $domain ) {
    switch ( $translated ) {
        case 'Sale!':
        case 'Výprodej!':
            return 'Výhodnější';
        case 'Unavailable':
        case 'Nedostupné':
            return 'Momentálně vyprodáno';
    }
    return $translated;
}, 20, 3 );

// ============================================================
//  VARIANTY – disabled option + stálý hlídací pes
// ============================================================

/**
 * Přidá "disabled" + suffix " – není skladem" k out-of-stock <option> ve variantách.
 * Zákazník ji vůbec nemůže vybrat.
 */
add_filter( 'woocommerce_dropdown_variation_attribute_options_html', function( $html, $args ) {
    $product   = $args['product'] ?? null;
    $attribute = $args['attribute'] ?? '';

    if ( ! $product || ! $product->is_type( 'variable' ) || ! $attribute ) {
        return $html;
    }

    // Mapa: hodnota atributu => existuje alespoň jedna skladová varianta?
    $stock_map = [];

    $available_variations = $product->get_available_variations();
    foreach ( $available_variations as $variation ) {
        $attr_key = 'attribute_' . sanitize_title( $attribute );
        $val      = $variation['attributes'][ $attr_key ] ?? '';

        if ( $val === '' ) {
            continue;
        }

        if ( ! isset( $stock_map[ $val ] ) ) {
            $stock_map[ $val ] = false;
        }

        if ( ! empty( $variation['is_in_stock'] ) ) {
            $stock_map[ $val ] = true;
        }
    }

    $html = preg_replace_callback(
        '/<option([^>]*)value="([^"]*)"([^>]*)>(.*?)<\/option>/iu',
        function( $m ) use ( $stock_map ) {
            $before = $m[1];
            $value  = html_entity_decode( $m[2], ENT_QUOTES, 'UTF-8' );
            $after  = $m[3];
            $label  = wp_strip_all_tags( html_entity_decode( $m[4], ENT_QUOTES, 'UTF-8' ) );

            // Placeholder "Vyberte možnost"
            if ( $value === '' ) {
                return $m[0];
            }

            $in_stock = $stock_map[ $value ] ?? true;

            // Odstranit případné staré atributy / suffix, ať se neduplikují
            $before = preg_replace( '/\sdata-stock="[^"]*"/i', '', $before );
            $after  = preg_replace( '/\sdisabled(\s*=\s*"disabled")?/i', '', $after );
            $label  = preg_replace( '/\s+–\s+není skladem$/u', '', $label );

            if ( ! $in_stock ) {
                $label .= ' – není skladem';
                $before .= ' data-stock="outofstock"';
                $after  .= ' disabled="disabled"';
            } else {
                $before .= ' data-stock="instock"';
            }

            return '<option' . $before . ' value="' . esc_attr( $value ) . '"' . $after . '>' . esc_html( $label ) . '</option>';
        },
        $html
    );

    return $html;
}, 10, 2 );


/**
 * Hlídací pes pro simple a bundle produkty na stránce produktu.
 * Zobrazí se automaticky když je produkt OOS.
 */
add_action( 'woocommerce_single_product_summary', function() {
    global $product;
    if ( ! $product ) return;
    // Jen simple a bundle (ne variable – to řeší woocommerce_single_variation níže)
    if ( $product->is_type('variable') ) return;
    // Zobrazit jen pokud není skladem
    if ( $product->is_in_stock() ) return;

    $product_id = $product->get_id();
    $nonce      = wp_create_nonce( 'stock_notify_nonce' );

    // Použijeme stejnou SVG proměnnou – definujeme ji zde lokálně (totožná s variable verzí)
    $hp_svg_simple = '<svg class="sp-hlidaci-icon" xmlns="http://www.w3.org/2000/svg" viewBox="26 15 411 401" fill-rule="evenodd" aria-hidden="true"><path d="M0 0 C38.08987379 34.85317269 61.88574382 84.40217613 65 136 C66.4473474 175.18756063 59.61433871 212.26540064 41 247 C40.65743164 247.65564941 40.31486328 248.31129883 39.96191406 248.98681641 C31.74901637 264.67398678 21.29897077 278.30079154 9 291 C8.49887695 291.5269043 7.99775391 292.05380859 7.48144531 292.59667969 C-26.69675167 328.47846281 -74.99049641 350.89410598 -124.69921875 352.203125 C-179.14422839 352.91843446 -228.60550042 333.5360184 -268.08984375 296.09375 C-273.07033851 291.23200476 -273.07033851 291.23200476 -275 289 C-275 288.34 -275 287.68 -275 287 C-275.66 287 -276.32 287 -277 287 C-280.25714194 283.55673567 -283.09517385 279.74209956 -286 276 C-286.44085938 275.43796875 -286.88171875 274.8759375 -287.3359375 274.296875 C-309.51185953 245.83991147 -324.45798734 209.97212857 -328 174 C-328.10957031 173.01257813 -328.21914063 172.02515625 -328.33203125 171.0078125 C-333.45683112 117.29990989 -318.00941593 64.21027967 -284.375 22 C-282.93506653 20.31741107 -281.47572593 18.65128643 -280 17 C-279.54947266 16.49065918 -279.09894531 15.98131836 -278.63476562 15.45654297 C-262.43570827 -2.79172758 -243.78473966 -17.89097438 -222 -29 C-221.35450195 -29.33692871 -220.70900391 -29.67385742 -220.04394531 -30.02099609 C-201.92127019 -39.40515221 -182.13692921 -45.75652709 -162 -49 C-161.28247559 -49.11569336 -160.56495117 -49.23138672 -159.82568359 -49.35058594 C-101.51423845 -58.24443387 -43.68893013 -38.66535137 0 0 Z M-232 -3 C-233.47919922 -2.01386719 -233.47919922 -2.01386719 -234.98828125 -1.0078125 C-242.62321779 4.31785959 -249.30940761 10.56103631 -256 17 C-256.56041992 17.53318848 -257.12083984 18.06637695 -257.69824219 18.61572266 C-268.53812685 28.9606212 -277.29703445 40.0975327 -285 53 C-285.37769531 53.62583984 -285.75539063 54.25167969 -286.14453125 54.89648438 C-311.30897854 96.95706055 -318.18633681 148.24508314 -306.41796875 195.6953125 C-303.12596122 208.30894746 -298.88415424 220.34937461 -293 232 C-292.45037598 233.10093994 -292.45037598 233.10093994 -291.88964844 234.22412109 C-286.95461755 244.0643098 -281.89349328 253.36543963 -275 262 C-274.42378906 262.76570313 -273.84757812 263.53140625 -273.25390625 264.3203125 C-263.96158553 276.43030467 -253.267887 287.89358528 -241 297 C-240.48147461 297.39348633 -239.96294922 297.78697266 -239.42871094 298.19238281 C-201.87136833 326.62074303 -154.52197404 340.14467062 -107.58984375 333.75 C-86.18541421 330.44343592 -65.96008072 323.40917142 -47 313 C-46.04915527 312.47817139 -46.04915527 312.47817139 -45.07910156 311.94580078 C-31.39501757 304.32220988 -19.37091631 294.74862736 -8 284 C-7.19175781 283.24460937 -6.38351562 282.48921875 -5.55078125 281.7109375 C27.62213198 249.73579037 46.79237196 202.70731195 48.18359375 156.91796875 C49.07745007 108.24794373 33.1551512 63.23646836 1.29443359 26.49414062 C0.12333522 25.14236355 -1.03850021 23.78257524 -2.19921875 22.421875 C-6.46809272 17.49044496 -10.96815389 13.14497157 -16 9 C-16.72574219 8.38640625 -17.45148438 7.7728125 -18.19921875 7.140625 C-79.27464582 -43.33493456 -166.4550378 -46.89057941 -232 -3 Z " fill="currentColor" transform="translate(354,64)"/><path d="M0 0 C13.10907872 7.63635653 21.07681501 19.23679931 25.00390625 33.828125 C25.25011719 34.87484375 25.49632812 35.9215625 25.75 37 C26.10384766 38.44632813 26.10384766 38.44632813 26.46484375 39.921875 C29.25190771 53.4017922 27.98984185 68.80227285 21.10546875 80.88671875 C15.98666267 88.52589558 9.9918183 94.05068992 0.75 96 C-7.12945578 96.93511051 -13.99492133 95.05337794 -20.44921875 90.421875 C-22.48723008 88.69829971 -24.37535753 86.89904129 -26.25 85 C-26.77207031 84.51144531 -27.29414063 84.02289063 -27.83203125 83.51953125 C-31.52216952 79.73202785 -33.7554819 75.30695291 -36 70.5625 C-36.29398682 69.94181641 -36.58797363 69.32113281 -36.89086914 68.68164062 C-43.46675072 54.14047267 -44.61214062 35.58458266 -39.3671875 20.453125 C-35.41221642 11.17295665 -29.9270954 4.28171024 -21.25 -1 C-14.40727555 -3.28090815 -6.5481373 -3.06333582 0 0 Z " fill="currentColor" transform="translate(186.25,83)"/><path d="M0 0 C9.24078575 9.24078575 12.24157761 23.31392209 12.375 35.9375 C12.19923537 52.63114538 7.05911281 68.3668545 -4 81 C-11.3372981 87.74535848 -19.28219164 91.47232736 -29.27734375 91.33984375 C-36.38499659 90.45266183 -42.61156066 86.34304056 -47.40234375 81.14453125 C-57.269101 67.90039988 -58.85190739 51.82506835 -56.71142578 35.84570312 C-54.19307688 22.09450405 -47.69405549 7.64606369 -36.4296875 -0.96484375 C-23.67405751 -9.65217392 -12.20027307 -10.01627357 0 0 Z " fill="currentColor" transform="translate(288,88)"/><path d="M0 0 C13.196681 10.38887653 20.83708038 23.43720053 23.5390625 40 C24.73298875 52.66117136 22.81431112 64.50727633 14.75 74.625 C9.53755992 80.16944264 4.01841813 82.48530314 -3.5625 82.75 C-14.30392952 82.27127334 -21.50572257 77.89159381 -29.0078125 70.359375 C-30.78690589 68.40314244 -32.44383327 66.40293894 -34.0625 64.3125 C-34.51367188 63.73242188 -34.96484375 63.15234375 -35.4296875 62.5546875 C-43.69379825 50.69748513 -46.73340753 34.39996266 -44.6015625 20.25390625 C-42.42140079 10.18668896 -37.69648505 2.14093384 -29.0625 -3.6875 C-18.98556395 -8.61400207 -9.18023205 -5.05849521 0 0 Z " fill="currentColor" transform="translate(130.0625,158.6875)"/><path d="M0 0 C7.22539142 5.21914592 10.50859859 12.9868975 12.61328125 21.47265625 C15.36874279 39.48057154 9.91062756 55.81574038 -0.6875 70.25 C-6.80326357 77.62178411 -15.24767814 83.12367408 -24.859375 84.37109375 C-33.23319246 84.56346523 -39.21585037 82.85886354 -45.77734375 77.56640625 C-53.10198279 70.09266515 -55.49440672 59.83602639 -55.625 49.6875 C-55.40957827 32.82570088 -48.8907734 18.55359508 -37.43359375 6.265625 C-26.85306162 -3.58462963 -12.8957008 -8.04480404 0 0 Z " fill="currentColor" transform="translate(346,157)"/><path d="M0 0 C0.66515625 0.25523437 1.3303125 0.51046875 2.015625 0.7734375 C15.07193571 6.37634249 23.28356247 18.66570807 29.75 30.6875 C37.27237328 44.63766419 47.01838519 56.12538679 59 66.5 C70.65594283 76.60336198 81.71723592 88.27414371 83.22314453 104.38769531 C84.15016976 119.98148026 82.39821193 132.74127302 72 145 C64.67717828 153.15398348 55.58623339 157.43549822 44.75 159 C32.03073071 159.58563542 19.65056139 155.51233565 7.75073242 151.51367188 C-9.27314484 145.79472533 -26.71283507 144.55958791 -43.95263672 150.32763672 C-47.26821105 151.4164862 -50.61847089 152.38746408 -53.96435547 153.37841797 C-57.29735267 154.36869524 -60.62306199 155.38219618 -63.94799805 156.39916992 C-75.17248859 159.68572212 -88.91601324 159.85608065 -99.4609375 154.5390625 C-111.37640712 147.31645476 -118.0238092 138.38223356 -122 125 C-122.28810547 124.09701172 -122.28810547 124.09701172 -122.58203125 123.17578125 C-125.10994416 110.01645891 -122.6771619 96.72699595 -115.8125 85.3125 C-108.13577528 74.0814355 -98.02783209 65.19577272 -87.96484375 56.17578125 C-79.28451205 48.0289924 -73.21015401 37.53670002 -67.4375 27.25 C-59.43514391 13.11901022 -49.56864767 2.50684805 -33.57421875 -2.34375 C-22.74674584 -4.33174503 -10.18494841 -4.53980259 0 0 Z M-48 36 C-49.299375 37.1446875 -49.299375 37.1446875 -50.625 38.3125 C-59.60247338 48.47121988 -62.96691767 58.46932812 -63 72 C-62.19763413 83.75640421 -56.24971695 93.84742541 -47.796875 101.80078125 C-36.86106701 110.39974759 -25.50354381 112.24047476 -12 111 C-8.09704136 110.07138087 -4.62233509 108.70426896 -1 107 C0.39477672 106.41414475 1.790475 105.83047361 3.1875 105.25 C4.115625 104.8375 5.04375 104.425 6 104 C8.54263365 106.20361583 9.87880939 107.61208421 10.89453125 110.86328125 C12.82352749 116.33672289 17.03737598 119.87506511 21 124 C22.06889002 125.17731364 23.1365434 126.35575091 24.203125 127.53515625 C25.32140382 128.73318477 26.44118478 129.92981298 27.5625 131.125 C28.07981689 131.70580322 28.59713379 132.28660645 29.13012695 132.88500977 C32.02379571 135.89270581 33.95540247 137.37494984 38.21875 137.50390625 C42.09514823 137.29909527 42.09514823 137.29909527 44.9375 134.8125 C47.04144916 131.94347842 48 130.53868291 48 127 C45.0099272 121.16194717 40.23410566 116.86201871 35.64892578 112.27832031 C34.12585805 110.75086052 32.61952275 109.20809501 31.11328125 107.6640625 C30.14160715 106.68652276 29.16898236 105.70992689 28.1953125 104.734375 C27.31891113 103.84975586 26.44250977 102.96513672 25.53955078 102.05371094 C22.86130045 99.88783497 21.35257421 99.41844696 18 99 C16.125 97.6875 16.125 97.6875 15 96 C15.64667046 91.7596894 17.87645423 88.18908727 19.84375 84.4375 C23.9940177 75.68828701 23.22063995 62.68323569 20.5 53.5625 C15.67780069 41.36318174 7.85900879 34.08318111 -3.8125 28.6875 C-18.53525112 22.33177684 -36.14393443 25.3934027 -48 36 Z " fill="currentColor" transform="translate(242,190)"/><path d="M0 0 C9.38028705 5.43471176 14.09474962 11.87779894 17.6875 21.9375 C19.58636743 30.79888133 18.25092309 39.13850529 13.99609375 47.15234375 C8.90296004 55.0106591 2.51670199 59.98246609 -6.5625 62.4375 C-17.66227272 63.43601512 -27.2969866 62.78697202 -36.375 55.6875 C-43.80291247 48.67904611 -46.86348106 39.96167674 -47.9375 29.9375 C-46.92148873 20.45472816 -44.19040309 12.48504687 -37.5625 5.4375 C-34.39322094 3.04941609 -31.14251941 1.14117483 -27.5625 -0.5625 C-26.79292969 -0.93375 -26.02335937 -1.305 -25.23046875 -1.6875 C-17.11813938 -4.34805897 -7.64664931 -3.62057259 0 0 Z M-24.5625 7.4375 C-24.913125 8.530625 -25.26375 9.62375 -25.625 10.75 C-26.93933541 13.90185287 -28.09814713 15.06066459 -31.25 16.375 C-32.343125 16.725625 -33.43625 17.07625 -34.5625 17.4375 C-34.5625 18.0975 -34.5625 18.7575 -34.5625 19.4375 C-32.9228125 19.6540625 -32.9228125 19.6540625 -31.25 19.875 C-29.3203125 20.39453125 -29.3203125 20.39453125 -27.5625 21.4375 C-26.03406347 24.32044753 -25.24941621 27.25805924 -24.5625 30.4375 C-22.15284453 29.52237697 -22.15284453 29.52237697 -21.375 26.0625 C-20.45915668 23.73950159 -19.93135486 22.67544432 -17.8125 21.30859375 C-16.09799761 20.59306553 -14.33154567 20.00492974 -12.5625 19.4375 C-12.5625 18.7775 -12.5625 18.1175 -12.5625 17.4375 C-15.2025 16.7775 -17.8425 16.1175 -20.5625 15.4375 C-20.85125 14.303125 -21.14 13.16875 -21.4375 12 C-21.80875 10.824375 -22.18 9.64875 -22.5625 8.4375 C-23.2225 8.1075 -23.8825 7.7775 -24.5625 7.4375 Z M0.4375 19.4375 C0.210625 20.200625 -0.01625 20.96375 -0.25 21.75 C-1.82981682 24.98486301 -3.39532717 25.85391358 -6.5625 27.4375 C-5.77875 28.015 -4.995 28.5925 -4.1875 29.1875 C-1.76202756 31.26647637 -0.70555583 32.52790333 0.4375 35.4375 C2.6611068 33.5120356 2.6611068 33.5120356 3.4375 30.4375 C6 29.25 6 29.25 8.4375 28.4375 C7.62458472 26.26843085 7.62458472 26.26843085 5.5 25.625 C2.75593292 24.04508259 2.36859876 22.3859794 1.4375 19.4375 C1.1075 19.4375 0.7775 19.4375 0.4375 19.4375 Z M-17.5625 36.4375 C-17.8925 38.0875 -18.2225 39.7375 -18.5625 41.4375 C-20.5425 42.0975 -22.5225 42.7575 -24.5625 43.4375 C-22.6370356 45.6611068 -22.6370356 45.6611068 -19.5625 46.4375 C-18.375 49 -18.375 49 -17.5625 51.4375 C-16.9025 51.4375 -16.2425 51.4375 -15.5625 51.4375 C-15.2221875 50.261875 -15.2221875 50.261875 -14.875 49.0625 C-13.5625 46.4375 -13.5625 46.4375 -10.9375 45.125 C-10.15375 44.898125 -9.37 44.67125 -8.5625 44.4375 C-9.38130887 42.27397603 -9.38130887 42.27397603 -11.5 41.5625 C-13.98251234 40.20840236 -14.53121397 39.01571507 -15.5625 36.4375 C-16.2225 36.4375 -16.8825 36.4375 -17.5625 36.4375 Z " fill="currentColor" transform="translate(236.5625,228.5625)"/></svg>';

    echo '<div class="sp-hlidaci-pes-variants" id="hp-simple-wrapper">';
    echo   '<div class="sp-hlidaci-pes-inline" data-product-id="' . esc_attr( $product_id ) . '" data-variation-id="0">';
    echo     '<p class="sp-hlidaci-label">' . esc_html__( 'Mám Tě informovat, až bude produkt naskladněn?', 'hlidaci-pes' ) . '</p>';
    echo     '<div class="sp-hlidaci-form">';
    echo       $hp_svg_simple;
    echo       '<input type="email" class="sp-hlidaci-email" placeholder="' . esc_attr__( 'Tvůj e-mail', 'hlidaci-pes' ) . '">';
    echo       '<input type="hidden" name="hp_nonce" value="' . esc_attr( $nonce ) . '">';
    echo       '<button type="button" class="sp-hlidaci-btn custom-product-btn" title="' . esc_attr__( 'Upozorníme Tě mailem, jakmile bude zboží skladem.', 'hlidaci-pes' ) . '">' . esc_html__( 'Hlídací pes', 'hlidaci-pes' ) . '</button>';
    echo     '</div>';
    echo     '<p class="sp-hlidaci-response" style="display:none;"></p>';
    echo   '</div>';
    echo '</div>';
}, 31 ); // priorita 31 = hned za cenou (30)


/**
 * Vloží formulář hlídacího psa pod single_variation (vždy viditelný).
 * JS dynamicky přepíná zprávu a variation_id, stav "registered" drží v poli.
 */
add_action( 'woocommerce_single_variation', function() {
    global $product;
    if ( ! $product || ! $product->is_type( 'variable' ) ) return;

    $product_id = $product->get_id();
    $nonce      = wp_create_nonce( 'stock_notify_nonce' );

    // SVG ikony psa (stejná jako v archivu – malá verze)
    // viewBox spočítán z translate(354,64) + rozsahu cest:
    // x: 354 + (-328) = 26  …  354 + 83 = 437  → šířka 411
    // y: 64 + (-49)   = 15  …  64 + 352 = 416  → výška 401
$hp_svg = '<svg class="sp-hlidaci-icon" xmlns="http://www.w3.org/2000/svg" viewBox="26 15 411 401" fill-rule="evenodd" aria-hidden="true"><path d="M0 0 C38.08987379 34.85317269 61.88574382 84.40217613 65 136 C66.4473474 175.18756063 59.61433871 212.26540064 41 247 C40.65743164 247.65564941 40.31486328 248.31129883 39.96191406 248.98681641 C31.74901637 264.67398678 21.29897077 278.30079154 9 291 C8.49887695 291.5269043 7.99775391 292.05380859 7.48144531 292.59667969 C-26.69675167 328.47846281 -74.99049641 350.89410598 -124.69921875 352.203125 C-179.14422839 352.91843446 -228.60550042 333.5360184 -268.08984375 296.09375 C-273.07033851 291.23200476 -273.07033851 291.23200476 -275 289 C-275 288.34 -275 287.68 -275 287 C-275.66 287 -276.32 287 -277 287 C-280.25714194 283.55673567 -283.09517385 279.74209956 -286 276 C-286.44085938 275.43796875 -286.88171875 274.8759375 -287.3359375 274.296875 C-309.51185953 245.83991147 -324.45798734 209.97212857 -328 174 C-328.10957031 173.01257813 -328.21914063 172.02515625 -328.33203125 171.0078125 C-333.45683112 117.29990989 -318.00941593 64.21027967 -284.375 22 C-282.93506653 20.31741107 -281.47572593 18.65128643 -280 17 C-279.54947266 16.49065918 -279.09894531 15.98131836 -278.63476562 15.45654297 C-262.43570827 -2.79172758 -243.78473966 -17.89097438 -222 -29 C-221.35450195 -29.33692871 -220.70900391 -29.67385742 -220.04394531 -30.02099609 C-201.92127019 -39.40515221 -182.13692921 -45.75652709 -162 -49 C-161.28247559 -49.11569336 -160.56495117 -49.23138672 -159.82568359 -49.35058594 C-101.51423845 -58.24443387 -43.68893013 -38.66535137 0 0 Z M-232 -3 C-233.47919922 -2.01386719 -233.47919922 -2.01386719 -234.98828125 -1.0078125 C-242.62321779 4.31785959 -249.30940761 10.56103631 -256 17 C-256.56041992 17.53318848 -257.12083984 18.06637695 -257.69824219 18.61572266 C-268.53812685 28.9606212 -277.29703445 40.0975327 -285 53 C-285.37769531 53.62583984 -285.75539063 54.25167969 -286.14453125 54.89648438 C-311.30897854 96.95706055 -318.18633681 148.24508314 -306.41796875 195.6953125 C-303.12596122 208.30894746 -298.88415424 220.34937461 -293 232 C-292.45037598 233.10093994 -292.45037598 233.10093994 -291.88964844 234.22412109 C-286.95461755 244.0643098 -281.89349328 253.36543963 -275 262 C-274.42378906 262.76570313 -273.84757812 263.53140625 -273.25390625 264.3203125 C-263.96158553 276.43030467 -253.267887 287.89358528 -241 297 C-240.48147461 297.39348633 -239.96294922 297.78697266 -239.42871094 298.19238281 C-201.87136833 326.62074303 -154.52197404 340.14467062 -107.58984375 333.75 C-86.18541421 330.44343592 -65.96008072 323.40917142 -47 313 C-46.04915527 312.47817139 -46.04915527 312.47817139 -45.07910156 311.94580078 C-31.39501757 304.32220988 -19.37091631 294.74862736 -8 284 C-7.19175781 283.24460937 -6.38351562 282.48921875 -5.55078125 281.7109375 C27.62213198 249.73579037 46.79237196 202.70731195 48.18359375 156.91796875 C49.07745007 108.24794373 33.1551512 63.23646836 1.29443359 26.49414062 C0.12333522 25.14236355 -1.03850021 23.78257524 -2.19921875 22.421875 C-6.46809272 17.49044496 -10.96815389 13.14497157 -16 9 C-16.72574219 8.38640625 -17.45148438 7.7728125 -18.19921875 7.140625 C-79.27464582 -43.33493456 -166.4550378 -46.89057941 -232 -3 Z " fill="currentColor" transform="translate(354,64)"/><path d="M0 0 C13.10907872 7.63635653 21.07681501 19.23679931 25.00390625 33.828125 C25.25011719 34.87484375 25.49632812 35.9215625 25.75 37 C26.10384766 38.44632813 26.10384766 38.44632813 26.46484375 39.921875 C29.25190771 53.4017922 27.98984185 68.80227285 21.10546875 80.88671875 C15.98666267 88.52589558 9.9918183 94.05068992 0.75 96 C-7.12945578 96.93511051 -13.99492133 95.05337794 -20.44921875 90.421875 C-22.48723008 88.69829971 -24.37535753 86.89904129 -26.25 85 C-26.77207031 84.51144531 -27.29414063 84.02289063 -27.83203125 83.51953125 C-31.52216952 79.73202785 -33.7554819 75.30695291 -36 70.5625 C-36.29398682 69.94181641 -36.58797363 69.32113281 -36.89086914 68.68164062 C-43.46675072 54.14047267 -44.61214062 35.58458266 -39.3671875 20.453125 C-35.41221642 11.17295665 -29.9270954 4.28171024 -21.25 -1 C-14.40727555 -3.28090815 -6.5481373 -3.06333582 0 0 Z " fill="currentColor" transform="translate(186.25,83)"/><path d="M0 0 C9.24078575 9.24078575 12.24157761 23.31392209 12.375 35.9375 C12.19923537 52.63114538 7.05911281 68.3668545 -4 81 C-11.3372981 87.74535848 -19.28219164 91.47232736 -29.27734375 91.33984375 C-36.38499659 90.45266183 -42.61156066 86.34304056 -47.40234375 81.14453125 C-57.269101 67.90039988 -58.85190739 51.82506835 -56.71142578 35.84570312 C-54.19307688 22.09450405 -47.69405549 7.64606369 -36.4296875 -0.96484375 C-23.67405751 -9.65217392 -12.20027307 -10.01627357 0 0 Z " fill="currentColor" transform="translate(288,88)"/><path d="M0 0 C13.196681 10.38887653 20.83708038 23.43720053 23.5390625 40 C24.73298875 52.66117136 22.81431112 64.50727633 14.75 74.625 C9.53755992 80.16944264 4.01841813 82.48530314 -3.5625 82.75 C-14.30392952 82.27127334 -21.50572257 77.89159381 -29.0078125 70.359375 C-30.78690589 68.40314244 -32.44383327 66.40293894 -34.0625 64.3125 C-34.51367188 63.73242188 -34.96484375 63.15234375 -35.4296875 62.5546875 C-43.69379825 50.69748513 -46.73340753 34.39996266 -44.6015625 20.25390625 C-42.42140079 10.18668896 -37.69648505 2.14093384 -29.0625 -3.6875 C-18.98556395 -8.61400207 -9.18023205 -5.05849521 0 0 Z " fill="currentColor" transform="translate(130.0625,158.6875)"/><path d="M0 0 C7.22539142 5.21914592 10.50859859 12.9868975 12.61328125 21.47265625 C15.36874279 39.48057154 9.91062756 55.81574038 -0.6875 70.25 C-6.80326357 77.62178411 -15.24767814 83.12367408 -24.859375 84.37109375 C-33.23319246 84.56346523 -39.21585037 82.85886354 -45.77734375 77.56640625 C-53.10198279 70.09266515 -55.49440672 59.83602639 -55.625 49.6875 C-55.40957827 32.82570088 -48.8907734 18.55359508 -37.43359375 6.265625 C-26.85306162 -3.58462963 -12.8957008 -8.04480404 0 0 Z " fill="currentColor" transform="translate(346,157)"/><path d="M0 0 C0.66515625 0.25523437 1.3303125 0.51046875 2.015625 0.7734375 C15.07193571 6.37634249 23.28356247 18.66570807 29.75 30.6875 C37.27237328 44.63766419 47.01838519 56.12538679 59 66.5 C70.65594283 76.60336198 81.71723592 88.27414371 83.22314453 104.38769531 C84.15016976 119.98148026 82.39821193 132.74127302 72 145 C64.67717828 153.15398348 55.58623339 157.43549822 44.75 159 C32.03073071 159.58563542 19.65056139 155.51233565 7.75073242 151.51367188 C-9.27314484 145.79472533 -26.71283507 144.55958791 -43.95263672 150.32763672 C-47.26821105 151.4164862 -50.61847089 152.38746408 -53.96435547 153.37841797 C-57.29735267 154.36869524 -60.62306199 155.38219618 -63.94799805 156.39916992 C-75.17248859 159.68572212 -88.91601324 159.85608065 -99.4609375 154.5390625 C-111.37640712 147.31645476 -118.0238092 138.38223356 -122 125 C-122.28810547 124.09701172 -122.28810547 124.09701172 -122.58203125 123.17578125 C-125.10994416 110.01645891 -122.6771619 96.72699595 -115.8125 85.3125 C-108.13577528 74.0814355 -98.02783209 65.19577272 -87.96484375 56.17578125 C-79.28451205 48.0289924 -73.21015401 37.53670002 -67.4375 27.25 C-59.43514391 13.11901022 -49.56864767 2.50684805 -33.57421875 -2.34375 C-22.74674584 -4.33174503 -10.18494841 -4.53980259 0 0 Z M-48 36 C-49.299375 37.1446875 -49.299375 37.1446875 -50.625 38.3125 C-59.60247338 48.47121988 -62.96691767 58.46932812 -63 72 C-62.19763413 83.75640421 -56.24971695 93.84742541 -47.796875 101.80078125 C-36.86106701 110.39974759 -25.50354381 112.24047476 -12 111 C-8.09704136 110.07138087 -4.62233509 108.70426896 -1 107 C0.39477672 106.41414475 1.790475 105.83047361 3.1875 105.25 C4.115625 104.8375 5.04375 104.425 6 104 C8.54263365 106.20361583 9.87880939 107.61208421 10.89453125 110.86328125 C12.82352749 116.33672289 17.03737598 119.87506511 21 124 C22.06889002 125.17731364 23.1365434 126.35575091 24.203125 127.53515625 C25.32140382 128.73318477 26.44118478 129.92981298 27.5625 131.125 C28.07981689 131.70580322 28.59713379 132.28660645 29.13012695 132.88500977 C32.02379571 135.89270581 33.95540247 137.37494984 38.21875 137.50390625 C42.09514823 137.29909527 42.09514823 137.29909527 44.9375 134.8125 C47.04144916 131.94347842 48 130.53868291 48 127 C45.0099272 121.16194717 40.23410566 116.86201871 35.64892578 112.27832031 C34.12585805 110.75086052 32.61952275 109.20809501 31.11328125 107.6640625 C30.14160715 106.68652276 29.16898236 105.70992689 28.1953125 104.734375 C27.31891113 103.84975586 26.44250977 102.96513672 25.53955078 102.05371094 C22.86130045 99.88783497 21.35257421 99.41844696 18 99 C16.125 97.6875 16.125 97.6875 15 96 C15.64667046 91.7596894 17.87645423 88.18908727 19.84375 84.4375 C23.9940177 75.68828701 23.22063995 62.68323569 20.5 53.5625 C15.67780069 41.36318174 7.85900879 34.08318111 -3.8125 28.6875 C-18.53525112 22.33177684 -36.14393443 25.3934027 -48 36 Z " fill="currentColor" transform="translate(242,190)"/><path d="M0 0 C9.38028705 5.43471176 14.09474962 11.87779894 17.6875 21.9375 C19.58636743 30.79888133 18.25092309 39.13850529 13.99609375 47.15234375 C8.90296004 55.0106591 2.51670199 59.98246609 -6.5625 62.4375 C-17.66227272 63.43601512 -27.2969866 62.78697202 -36.375 55.6875 C-43.80291247 48.67904611 -46.86348106 39.96167674 -47.9375 29.9375 C-46.92148873 20.45472816 -44.19040309 12.48504687 -37.5625 5.4375 C-34.39322094 3.04941609 -31.14251941 1.14117483 -27.5625 -0.5625 C-26.79292969 -0.93375 -26.02335937 -1.305 -25.23046875 -1.6875 C-17.11813938 -4.34805897 -7.64664931 -3.62057259 0 0 Z M-24.5625 7.4375 C-24.913125 8.530625 -25.26375 9.62375 -25.625 10.75 C-26.93933541 13.90185287 -28.09814713 15.06066459 -31.25 16.375 C-32.343125 16.725625 -33.43625 17.07625 -34.5625 17.4375 C-34.5625 18.0975 -34.5625 18.7575 -34.5625 19.4375 C-32.9228125 19.6540625 -32.9228125 19.6540625 -31.25 19.875 C-29.3203125 20.39453125 -29.3203125 20.39453125 -27.5625 21.4375 C-26.03406347 24.32044753 -25.24941621 27.25805924 -24.5625 30.4375 C-22.15284453 29.52237697 -22.15284453 29.52237697 -21.375 26.0625 C-20.45915668 23.73950159 -19.93135486 22.67544432 -17.8125 21.30859375 C-16.09799761 20.59306553 -14.33154567 20.00492974 -12.5625 19.4375 C-12.5625 18.7775 -12.5625 18.1175 -12.5625 17.4375 C-15.2025 16.7775 -17.8425 16.1175 -20.5625 15.4375 C-20.85125 14.303125 -21.14 13.16875 -21.4375 12 C-21.80875 10.824375 -22.18 9.64875 -22.5625 8.4375 C-23.2225 8.1075 -23.8825 7.7775 -24.5625 7.4375 Z M0.4375 19.4375 C0.210625 20.200625 -0.01625 20.96375 -0.25 21.75 C-1.82981682 24.98486301 -3.39532717 25.85391358 -6.5625 27.4375 C-5.77875 28.015 -4.995 28.5925 -4.1875 29.1875 C-1.76202756 31.26647637 -0.70555583 32.52790333 0.4375 35.4375 C2.6611068 33.5120356 2.6611068 33.5120356 3.4375 30.4375 C6 29.25 6 29.25 8.4375 28.4375 C7.62458472 26.26843085 7.62458472 26.26843085 5.5 25.625 C2.75593292 24.04508259 2.36859876 22.3859794 1.4375 19.4375 C1.1075 19.4375 0.7775 19.4375 0.4375 19.4375 Z M-17.5625 36.4375 C-17.8925 38.0875 -18.2225 39.7375 -18.5625 41.4375 C-20.5425 42.0975 -22.5225 42.7575 -24.5625 43.4375 C-22.6370356 45.6611068 -22.6370356 45.6611068 -19.5625 46.4375 C-18.375 49 -18.375 49 -17.5625 51.4375 C-16.9025 51.4375 -16.2425 51.4375 -15.5625 51.4375 C-15.2221875 50.261875 -15.2221875 50.261875 -14.875 49.0625 C-13.5625 46.4375 -13.5625 46.4375 -10.9375 45.125 C-10.15375 44.898125 -9.37 44.67125 -8.5625 44.4375 C-9.38130887 42.27397603 -9.38130887 42.27397603 -11.5 41.5625 C-13.98251234 40.20840236 -14.53121397 39.01571507 -15.5625 36.4375 C-16.2225 36.4375 -16.8825 36.4375 -17.5625 36.4375 Z " fill="currentColor" transform="translate(236.5625,228.5625)"/></svg>';

echo '<div id="hp-variation-wrapper">';
echo   '<p class="sp-hlidaci-label" id="hp-variation-msg" style="display:none;"></p>';
echo   '<p class="sp-hlidaci-label" id="hp-variation-question" style="display:none;">Mám Tě informovat, až bude naskladněna?</p>';
echo   '<div class="sp-hlidaci-form" id="hp-variation-form-row" style="display:none;">';
echo     '<span class="sp-hlidaci-icon-wrap">' . $hp_svg . '</span>';
echo     '<input type="email" id="hp-variation-email" class="sp-hlidaci-email" placeholder="' . esc_attr__( 'Tvůj e-mail', 'hlidaci-pes' ) . '">';
echo     '<input type="hidden" id="hp-variation-product-id" value="' . esc_attr( $product_id ) . '">';
echo     '<input type="hidden" id="hp-variation-variation-id" value="">';
echo     '<input type="hidden" id="hp-variation-nonce" value="' . esc_attr( $nonce ) . '">';
echo     '<button type="button" id="hp-variation-btn" class="sp-hlidaci-btn custom-product-btn">' . esc_html__( 'Hlídací pes', 'hlidaci-pes' ) . '</button>';
echo     '<button type="button" id="hp-variation-add-to-cart-dummy" class="button alt hp-add-to-cart-lookalike" tabindex="-1" aria-hidden="true">Přidat do košíku</button>';
echo   '</div>';
echo   '<p id="hp-variation-response" class="sp-hlidaci-response" style="display:none;"></p>';
echo '</div>';
}, 20 );


/**
 * JS pro stránku produktu s variantami:
 * - found_variation  → pokud OOS: zobraz formulář, nastav variation_id
 * - found_variation  → pokud IN STOCK: skryj formulář
 * - reset_data       → skryj formulář
 * - odeslání         → AJAX stock_notify
 */
add_action( 'wp_footer', function() {
    if ( ! is_product() ) return;
    global $product;
    if ( ! $product || ! $product->is_type( 'variable' ) ) return;
    ?>
<script>
(function($){
    $(document).ready(function(){

        var $form    = $('form.variations_form');
        var $wrapper = $('#hp-variation-wrapper');
        if ( ! $form.length || ! $wrapper.length ) return;

        var $msg      = $('#hp-variation-msg');
        var $question = $('#hp-variation-question');
        var $formRow  = $('#hp-variation-form-row');
        var $vidInput = $('#hp-variation-variation-id');
        var $resp     = $('#hp-variation-response');
        var $btn      = $('#hp-variation-btn');
        var $email    = $('#hp-variation-email');

        var registered = {};

        function enforceDisabledOptions() {
            $form.find('.variations select option[data-stock="outofstock"]').each(function(){
                $(this).prop('disabled', true).attr('disabled', 'disabled');
            });
        }

        function showWatchdog( variationId, labelHtml ) {
            if ( registered[ variationId ] ) {
                $msg.html( labelHtml ).css('color','#c00').show();
                $question.hide();
                $formRow.hide();
                $resp.text('✓ Hlídací pes je nastaven.').css('color','green').show();
                return;
            }

            $vidInput.val( variationId );
            $msg.html( labelHtml ).css('color','#c00').show();
            $question.show();
            $formRow.css('display', 'flex');
            $resp.hide().text('');
            $email.show().val('');
            $btn.show().prop('disabled', false).text('Hlídací pes');
        }

        function hideWatchdog() {
            $msg.hide();
            $question.hide();
            $formRow.hide();
            $resp.hide();
        }

        enforceDisabledOptions();

        $form.on('woocommerce_variation_has_changed', function(){
            enforceDisabledOptions();
        });

        $form.on('found_variation', function(e, variation){
            enforceDisabledOptions();

            if ( ! variation.is_in_stock ) {
                var vid = variation.variation_id;
                var attrText = [];

                $form.find('.variations select').each(function(){
                    var val = $(this).find('option:selected').text() || $(this).val();
                    if ( val ) {
                        attrText.push( val.replace(/\s+–\s+není skladem$/i, '') );
                    }
                });

                var label = 'Varianta &ldquo;' + attrText.join(', ') + '&rdquo; není skladem.';
                showWatchdog( vid, label );
            } else {
                hideWatchdog();
            }
        });

        $form.on('reset_data hide_variation', function(){
            hideWatchdog();
            enforceDisabledOptions();
        });

        $btn.on('click', function(){
            var email = $email.val().trim();

            if ( ! email || ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) ) {
                $resp.text('Zadej platný e-mail.').css('color','red').show();
                return;
            }

            $btn.prop('disabled', true).text('…');

            var ajaxUrl = (typeof StockNotify !== 'undefined') ? StockNotify.ajax_url : '<?php echo esc_js( admin_url("admin-ajax.php") ); ?>';
            var nonce   = (typeof StockNotify !== 'undefined') ? StockNotify.nonce : $('#hp-variation-nonce').val();

            $.post( ajaxUrl, {
                action:         'stock_notify',
                security:       nonce,
                customer_email: email,
                product_id:     $('#hp-variation-product-id').val(),
                variation_id:   $vidInput.val(),
            }, function(response){
                $resp.text(response.data.message).show();

                if ( response.success ) {
                    $resp.css('color','green');
                    registered[ $vidInput.val() ] = true;
                    $email.hide();
                    $btn.hide();
                } else {
                    $resp.css('color','red');
                    $btn.prop('disabled', false).text('Hlídací pes');
                }
            }).fail(function(){
                $resp.text('Chyba spojení, zkus to znovu.').css('color','red').show();
                $btn.prop('disabled', false).text('Hlídací pes');
            });
        });
    });
})(jQuery);
</script>
    <?php
}, 20 );