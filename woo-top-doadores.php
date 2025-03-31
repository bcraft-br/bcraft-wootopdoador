<?php
/**
 * Plugin Name: Woo Top Doadores
 * Description: Exibe os top doadores da loja via shortcode com ranking mensal.
 * Version: 1.4.0
 * Author: Bcraft Brasil Minecraft
 * Text Domain: woo-top-doadores
 */

if (!defined('ABSPATH')) {
    exit; // Protege contra acesso direto
}

class WooTopDoadores {
    public function __construct() {
        // Hooks de administração
        add_action('admin_menu', array($this, 'wc_top_doadores_menu'));
        add_action('admin_init', array($this, 'wc_top_doadores_register_settings'));
        
        // Shortcodes
        add_shortcode('top_doadores', array($this, 'wc_top_doadores_shortcode'));
        add_shortcode('top_compradores', array($this, 'wc_top_doadores_shortcode')); // Mantido para compatibilidade
        
        // Estilos
        add_action('wp_enqueue_scripts', array($this, 'wc_top_doadores_enqueue_styles'));
    }

    // Mover o menu para o WooCommerce
    public function wc_top_doadores_menu() {
        add_submenu_page(
            'woocommerce',
            'Top Doadores',
            'Top Doadores',
            'manage_options',
            'wc_top_doadores',
            array($this, 'wc_top_doadores_settings_page')
        );
    }

    public function wc_top_doadores_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wc_top_doadores_options');
                do_settings_sections('wc_top_doadores');
                submit_button();
                ?>
            </form>
            <h3>Como usar</h3>
            <p>Use o shortcode <strong>[top_doadores]</strong> para exibir a lista dos top doadores do mês atual.</p>
            <p>Exemplos:<br>
            - Padrão (top 3): [top_doadores]<br>
            - Top 5: [top_doadores limit=5 background_color="#f5f5f5" border_radius="10px"]<br>
            - Ocultar período: [top_doadores show_period="false"]</p>
            <p><strong>Obs:</strong> O ranking é mensal e reinicia automaticamente no primeiro dia de cada mês.</p>
        </div>
        <?php
    }

    public function wc_top_doadores_register_settings() {
        register_setting('wc_top_doadores_options', 'wc_top_doadores_title');
        register_setting('wc_top_doadores_options', 'wc_top_doadores_font_size');
        register_setting('wc_top_doadores_options', 'wc_top_doadores_font_color');
        register_setting('wc_top_doadores_options', 'wc_top_doadores_background_color');
        register_setting('wc_top_doadores_options', 'wc_top_doadores_border_radius');
        register_setting('wc_top_doadores_options', 'wc_top_doadores_show_period');
        
        add_settings_section(
            'wc_top_doadores_section',
            'Configurações do Top Doadores Mensal',
            null,
            'wc_top_doadores'
        );

        add_settings_field(
            'wc_top_doadores_title',
            'Título do Top Doadores',
            array($this, 'wc_top_doadores_title_callback'),
            'wc_top_doadores',
            'wc_top_doadores_section'
        );

        add_settings_field(
            'wc_top_doadores_font_size',
            'Tamanho da Fonte',
            array($this, 'wc_top_doadores_font_size_callback'),
            'wc_top_doadores',
            'wc_top_doadores_section'
        );

        add_settings_field(
            'wc_top_doadores_font_color',
            'Cor da Fonte',
            array($this, 'wc_top_doadores_font_color_callback'),
            'wc_top_doadores',
            'wc_top_doadores_section'
        );

        add_settings_field(
            'wc_top_doadores_background_color',
            'Cor de Fundo',
            array($this, 'wc_top_doadores_background_color_callback'),
            'wc_top_doadores',
            'wc_top_doadores_section'
        );

        add_settings_field(
            'wc_top_doadores_border_radius',
            'Bordas Arredondadas',
            array($this, 'wc_top_doadores_border_radius_callback'),
            'wc_top_doadores',
            'wc_top_doadores_section'
        );

        add_settings_field(
            'wc_top_doadores_show_period',
            'Mostrar Período',
            array($this, 'wc_top_doadores_show_period_callback'),
            'wc_top_doadores',
            'wc_top_doadores_section'
        );
    }

    public function wc_top_doadores_title_callback() {
        $title = get_option('wc_top_doadores_title', 'Top Doadores do Mês');
        echo '<input type="text" name="wc_top_doadores_title" value="' . esc_attr($title) . '" class="regular-text">';
    }

    public function wc_top_doadores_font_size_callback() {
        $font_size = get_option('wc_top_doadores_font_size', '20');
        echo '<input type="number" name="wc_top_doadores_font_size" value="' . esc_attr($font_size) . '" class="small-text">px';
    }

    public function wc_top_doadores_font_color_callback() {
        $font_color = get_option('wc_top_doadores_font_color', '#000000');
        echo '<input type="color" name="wc_top_doadores_font_color" value="' . esc_attr($font_color) . '">';
    }

    public function wc_top_doadores_background_color_callback() {
        $background_color = get_option('wc_top_doadores_background_color', '#ffffff');
        echo '<input type="color" name="wc_top_doadores_background_color" value="' . esc_attr($background_color) . '">';
    }

    public function wc_top_doadores_border_radius_callback() {
        $border_radius = get_option('wc_top_doadores_border_radius', '5');
        echo '<input type="number" name="wc_top_doadores_border_radius" value="' . esc_attr($border_radius) . '" class="small-text">px';
    }

    public function wc_top_doadores_show_period_callback() {
        $show_period = get_option('wc_top_doadores_show_period', '1');
        echo '<label><input type="checkbox" name="wc_top_doadores_show_period" value="1" ' . checked(1, $show_period, false) . '> Exibir o período mensal</label>';
    }

    // Função para obter os top doadores do mês atual
    public function wc_get_top_doadores($limit = 3) {
        global $wpdb;

        $limit = absint($limit);
        $limit = ($limit > 10) ? 10 : $limit; // Limite máximo de 10

        // Obtém o primeiro e último dia do mês atual
        $first_day = date('Y-m-01 00:00:00');
        $last_day = date('Y-m-t 23:59:59');

        $query = $wpdb->prepare("
            SELECT 
                COALESCE(pm_billing_last.meta_value, 'Convidado') AS display_name,
                SUM(o.net_total) AS total_gasto
            FROM {$wpdb->prefix}wc_order_stats o
            INNER JOIN {$wpdb->prefix}posts p ON p.ID = o.order_id AND p.post_type = 'shop_order'
            LEFT JOIN {$wpdb->prefix}postmeta pm_billing_last ON p.ID = pm_billing_last.post_id 
                AND pm_billing_last.meta_key = '_billing_last_name'
            WHERE o.status = 'wc-completed'
            AND o.date_created >= %s
            AND o.date_created <= %s
            GROUP BY pm_billing_last.meta_value
            ORDER BY total_gasto DESC
            LIMIT %d
        ", $first_day, $last_day, $limit);

        $results = $wpdb->get_results($query);

        return $results;
    }

    // Shortcode aprimorado
    public function wc_top_doadores_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 3,
            'background_color' => get_option('wc_top_doadores_background_color', '#ffffff'),
            'border_radius' => get_option('wc_top_doadores_border_radius', '5'),
            'font_size' => get_option('wc_top_doadores_font_size', '20'),
            'font_color' => get_option('wc_top_doadores_font_color', '#000000'),
            'title' => get_option('wc_top_doadores_title', 'Top Doadores do Mês'),
            'show_medals' => 'true',
            'show_period' => get_option('wc_top_doadores_show_period', '1'),
            'custom_class' => ''
        ), $atts, 'top_doadores');

        $top_doadores = $this->wc_get_top_doadores($atts['limit']);

        if (empty($top_doadores)) {
            return '<p>Nenhum doador encontrado este mês.</p>';
        }

        // Processa atributos booleanos
        $show_medals = filter_var($atts['show_medals'], FILTER_VALIDATE_BOOLEAN);
        $show_period = filter_var($atts['show_period'], FILTER_VALIDATE_BOOLEAN);

        $title_style = "font-size: {$atts['font_size']}px; color: {$atts['font_color']};";
        $container_style = "background-color: {$atts['background_color']}; border-radius: {$atts['border_radius']}px; padding: 20px;";
        $item_style = "font-size: " . ($atts['font_size'] - 2) . "px; color: {$atts['font_color']};";

        $medalhas = array('🥇', '🥈', '🥉');
        $custom_class = !empty($atts['custom_class']) ? esc_attr($atts['custom_class']) : '';

        $output = "<div class='top-doadores-container {$custom_class}' style='{$container_style}'>";
        $output .= "<h2 class='top-doadores-title' style='{$title_style}'>{$atts['title']}</h2>";
        $output .= '<ul class="top-doadores-list" style="list-style: none; padding-left: 0; margin: 0;">';
        $posicao = 0;

        foreach ($top_doadores as $doador) {
            $nome = esc_html($doador->display_name);
            $medalha = ($show_medals && isset($medalhas[$posicao])) ? $medalhas[$posicao] . ' ' : '';
            $valor_formatado = number_format($doador->total_gasto, 2, ',', '.');

            $output .= '<li class="top-doadores-item" style="margin-bottom: 10px; padding: 5px 0; border-bottom: 1px solid rgba(0,0,0,0.1); ' . $item_style . '">' 
                     . $medalha . $nome . ' → R$' . $valor_formatado . '</li>';
            $posicao++;
        }

        $output .= '</ul>';
        
        // Adiciona informação sobre o período mensal se ativado
        if ($show_period) {
            $output .= '<p style="font-size: ' . ($atts['font_size'] - 4) . 'px; color: ' . $atts['font_color'] . '; opacity: 0.8; margin-top: 10px; margin-bottom: 0;">';
            $output .= 'Período: ' . date('01/m/Y') . ' a ' . date('t/m/Y');
            $output .= '</p>';
        }
        
        $output .= '</div>';

        return $output;
    }

    // Estilos
    public function wc_top_doadores_enqueue_styles() {
        $css_path = plugin_dir_path(__FILE__) . 'assets/css/top-doadores.css';
        
        if (file_exists($css_path)) {
            wp_enqueue_style(
                'top-doadores-style',
                plugins_url('assets/css/top-doadores.css', __FILE__),
                array(),
                filemtime($css_path)
            );
        }
    }
}

// Inicializa o plugin
function woo_top_doadores_init() {
    // Verifica se WooCommerce está ativo
    if (class_exists('WooCommerce')) {
        new WooTopDoadores();
    } else {
        add_action('admin_notices', 'woo_top_doadores_missing_wc_notice');
    }
}
add_action('plugins_loaded', 'woo_top_doadores_init');

// Mostra aviso se WooCommerce não estiver ativo
function woo_top_doadores_missing_wc_notice() {
    ?>
    <div class="notice notice-error">
        <p>O plugin Woo Top Doadores requer que o WooCommerce esteja instalado e ativado!</p>
    </div>
    <?php
}

// Hook de desinstalação
register_uninstall_hook(__FILE__, 'woo_top_doadores_uninstall');
function woo_top_doadores_uninstall() {
    // Remove todas as opções
    delete_option('wc_top_doadores_title');
    delete_option('wc_top_doadores_font_size');
    delete_option('wc_top_doadores_font_color');
    delete_option('wc_top_doadores_background_color');
    delete_option('wc_top_doadores_border_radius');
    delete_option('wc_top_doadores_show_period');
}