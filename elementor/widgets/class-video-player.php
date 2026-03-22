<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

defined('ABSPATH') || exit;

class VideoPlayer extends AbstractBtWidget {

    use BtSharedControls;

    private const PLYR_VERSION  = '3.7.8';
    private const PLYR_CDN_BASE = 'https://cdn.plyr.io';

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-video-player',
            'title'    => 'BT — Video Player',
            'icon'     => 'eicon-play',
            'keywords' => ['video', 'plyr', 'player', 'youtube', 'lazy', 'bt'],
            'css'      => ['bt-video-player'],
            'js'       => ['bt-video-player'],
        ];
    }

    public static function register_plyr_assets(): void {
        $v    = self::PLYR_VERSION;
        $base = self::PLYR_CDN_BASE;
        if (!wp_script_is('plyr-js', 'registered')) {
            wp_register_script('plyr-js', "{$base}/{$v}/plyr.polyfilled.js", [], $v, true);
        }
        if (!wp_style_is('plyr-css', 'registered')) {
            wp_register_style('plyr-css', "{$base}/{$v}/plyr.css", [], $v);
        }
    }

    public function get_script_depends(): array {
        return ['plyr-js', 'bt-video-player'];
    }

    public function get_style_depends(): array {
        return ['plyr-css', 'bt-video-player'];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Controls
    // ─────────────────────────────────────────────────────────────────────

    protected function register_controls(): void {
        $this->register_content_controls();
        $this->register_style_controls();
    }

    private function register_content_controls(): void {

        // ── Video source ─────────────────────────────────────────────
        $this->start_controls_section('section_video', [
            'label' => __('Video', 'blacktenderscore'),
        ]);

        $this->add_control('video_source', [
            'label'   => __('Source', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'url',
            'options' => [
                'url'  => 'URL (YouTube / Vimeo / MP4)',
                'file' => __('Fichier', 'blacktenderscore'),
                'acf'  => 'ACF',
            ],
        ]);

        $this->add_control('video_url', [
            'label'       => 'URL',
            'type'        => Controls_Manager::URL,
            'placeholder' => 'https://www.youtube.com/watch?v=...',
            'dynamic'     => ['active' => true],
            'condition'   => ['video_source' => 'url'],
        ]);

        $this->add_control('video_file', [
            'label'       => __('Fichier', 'blacktenderscore'),
            'type'        => Controls_Manager::MEDIA,
            'media_types' => ['video'],
            'condition'   => ['video_source' => 'file'],
        ]);

        $this->add_control('video_acf_field', [
            'label'     => __('Champ ACF', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'video',
            'condition' => ['video_source' => 'acf'],
        ]);

        $this->end_controls_section();

        // ── Poster ───────────────────────────────────────────────────
        $this->start_controls_section('section_poster', [
            'label' => 'Poster',
        ]);

        $this->add_control('poster_source', [
            'label'   => __('Image', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'featured',
            'options' => [
                'none'     => __('Aucun', 'blacktenderscore'),
                'featured' => __('Image mise en avant', 'blacktenderscore'),
                'custom'   => __('Personnalisee', 'blacktenderscore'),
                'acf'      => 'ACF',
            ],
        ]);

        $this->add_control('poster_image', [
            'label'     => __('Image', 'blacktenderscore'),
            'type'      => Controls_Manager::MEDIA,
            'condition' => ['poster_source' => 'custom'],
        ]);

        $this->add_control('poster_acf_field', [
            'label'     => __('Champ ACF', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'video_poster',
            'condition' => ['poster_source' => 'acf'],
        ]);

        $this->end_controls_section();

        // ── Player options ───────────────────────────────────────────
        $this->start_controls_section('section_options', [
            'label' => __('Options', 'blacktenderscore'),
        ]);

        $this->add_control('autoplay', [
            'label' => __('Autoplay', 'blacktenderscore'),
            'type'  => Controls_Manager::SWITCHER,
        ]);

        $this->add_control('muted', [
            'label' => __('Muet', 'blacktenderscore'),
            'type'  => Controls_Manager::SWITCHER,
        ]);

        $this->add_control('loop', [
            'label' => __('Boucle', 'blacktenderscore'),
            'type'  => Controls_Manager::SWITCHER,
        ]);

        $this->add_control('lazy_load', [
            'label'       => 'Lazy load (SEO)',
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'description' => __('preload=none jusqu\'au viewport', 'blacktenderscore'),
        ]);

        $this->add_control('lazy_margin', [
            'label'   => __('Marge preload', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'default' => '200px',
            'options' => ['0px' => '0px', '100px' => '100px', '200px' => '200px', '400px' => '400px'],
            'condition' => ['lazy_load' => 'yes'],
        ]);

        $this->add_control('plyr_controls', [
            'label'    => __('Controles', 'blacktenderscore'),
            'type'     => Controls_Manager::SELECT2,
            'multiple' => true,
            'default'  => ['play-large','play','progress','current-time','duration','mute','volume','settings','pip','fullscreen'],
            'options'  => [
                'play-large'   => 'Play (grand)',
                'play'         => 'Play/Pause',
                'progress'     => 'Progression',
                'current-time' => 'Temps',
                'duration'     => 'Duree',
                'mute'         => 'Mute',
                'volume'       => 'Volume',
                'settings'     => 'Settings',
                'pip'          => 'PiP',
                'fullscreen'   => 'Fullscreen',
            ],
        ]);

        $this->end_controls_section();
    }

    private function register_style_controls(): void {

        // ── Video container ──────────────────────────────────────────
        $this->start_controls_section('section_style_video', [
            'label' => __('Video', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('video_sizing_mode', [
            'label'   => __('Taille', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'ratio',
            'options' => [
                'ratio' => 'Ratio',
                'fixed' => __('Hauteur fixe', 'blacktenderscore'),
            ],
        ]);

        $this->add_responsive_control('video_aspect_ratio', [
            'label'   => 'Ratio',
            'type'    => Controls_Manager::SELECT,
            'default' => '16-9',
            'options' => [
                '16-9' => '16:9', '21-9' => '21:9', '4-3' => '4:3',
                '1-1'  => '1:1',  '9-16' => '9:16',
            ],
            'condition' => ['video_sizing_mode' => 'ratio'],
        ]);

        $this->add_responsive_control('video_height', [
            'label'      => __('Hauteur', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'vh', 'svh'],
            'range'      => [
                'px'  => ['min' => 100, 'max' => 1200, 'step' => 10],
                'vh'  => ['min' => 10, 'max' => 100],
                'svh' => ['min' => 10, 'max' => 100],
            ],
            'default'    => ['size' => 400, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-video-player__wrap' => 'height: {{SIZE}}{{UNIT}};'],
            'condition'  => ['video_sizing_mode' => 'fixed'],
        ]);

        $this->add_responsive_control('video_max_width', [
            'label'      => __('Largeur max', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%', 'vw'],
            'range'      => ['px' => ['min' => 200, 'max' => 1920], '%' => ['min' => 20, 'max' => 100]],
            'selectors'  => ['{{WRAPPER}} .bt-video-player' => 'max-width: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('video_border_radius', [
            'label'      => __('Coins', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-video-player__wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'video_shadow',
            'selector' => '{{WRAPPER}} .bt-video-player__wrap',
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'video_border',
            'selector' => '{{WRAPPER}} .bt-video-player__wrap',
        ]);

        $this->end_controls_section();

        // ── Play button (poster overlay) ─────────────────────────────
        // Size control (before the button_style section)
        $this->start_controls_section('section_style_play_size', [
            'label' => __('Bouton Play (poster)', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('play_btn_size', [
            'label'      => __('Taille', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'range'      => ['px' => ['min' => 32, 'max' => 120]],
            'default'    => ['size' => 68, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-video-player__play' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('play_btn_icon_size', [
            'label'      => __('Taille icone', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'range'      => ['px' => ['min' => 12, 'max' => 60]],
            'default'    => ['size' => 28, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-video-player__play svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->end_controls_section();

        // Full button style: normal/hover tabs, color, bg, border, padding, radius
        $this->register_button_style(
            'play_btn',
            __('Bouton Play — Style', 'blacktenderscore'),
            '{{WRAPPER}} .bt-video-player__play',
            ['color' => '#ffffff', 'bg' => 'rgba(0,0,0,0.6)']
        );

        // ── Plyr controls (bar buttons) ──────────────────────────────
        $this->register_button_style(
            'plyr_ctrl',
            __('Controles Plyr — Boutons', 'blacktenderscore'),
            '{{WRAPPER}} .plyr__controls .plyr__control'
        );

        // ── Plyr theming (CSS custom properties) ─────────────────────
        $this->start_controls_section('section_style_plyr', [
            'label' => __('Plyr — Couleurs globales', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $plyr_vars = [
            'plyr_accent_color'  => ['Couleur accent',     '--plyr-color-main'],
            'plyr_controls_bg'   => ['Fond barre',         '--plyr-video-controls-background'],
            'plyr_tooltip_bg'    => ['Fond tooltips',       '--plyr-tooltip-background'],
            'plyr_tooltip_color' => ['Texte tooltips',      '--plyr-tooltip-color'],
            'plyr_menu_bg'       => ['Fond menu',           '--plyr-menu-background'],
            'plyr_menu_color'    => ['Texte menu',          '--plyr-menu-color'],
            'plyr_badge_bg'      => ['Fond badge',          '--plyr-badge-background'],
        ];

        foreach ($plyr_vars as $id => [$label, $var]) {
            $this->add_control($id, [
                'label'     => __($label, 'blacktenderscore'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => ['{{WRAPPER}} .plyr' => "{$var}: {{VALUE}};"],
            ]);
        }

        $this->end_controls_section();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Render
    // ─────────────────────────────────────────────────────────────────────

    protected function render(): void {
        $s = $this->get_settings_for_display();

        $video_url = $this->resolve_video_url($s);
        if (!$video_url) {
            $this->render_placeholder(__('Aucune video configuree.', 'blacktenderscore'));
            return;
        }

        $video_type = $this->detect_type($video_url);
        $poster     = $this->resolve_poster($s);
        $lazy       = $s['lazy_load'] === 'yes';
        $autoplay   = $s['autoplay'] === 'yes';
        $muted      = $s['muted'] === 'yes' || $autoplay;
        $loop       = $s['loop'] === 'yes';
        $sizing     = $s['video_sizing_mode'] ?? 'ratio';
        $ratio      = $s['video_aspect_ratio'] ?? '16-9';

        $config = [
            'src'        => $video_url,
            'type'       => $video_type,
            'poster'     => $poster,
            'autoplay'   => $autoplay,
            'muted'      => $muted,
            'loop'       => $loop,
            'lazy'       => $lazy,
            'lazyMargin' => $s['lazy_margin'] ?? '200px',
            'plyr'       => [
                'controls'     => $s['plyr_controls'] ?? [],
                'settings'     => ['quality', 'speed'],
                'clickToPlay'  => true,
                'hideControls' => true,
                'invertTime'   => false,
                'loadSprite'   => true,
                'iconUrl'      => self::PLYR_CDN_BASE . '/' . self::PLYR_VERSION . '/plyr.svg',
            ],
        ];

        if ($video_type === 'youtube') $config['videoId'] = $this->extract_youtube_id($video_url);
        if ($video_type === 'vimeo')   $config['videoId'] = $this->extract_vimeo_id($video_url);

        $wrap_class = $sizing === 'fixed'
            ? ' bt-video-player__wrap--fixed'
            : " bt-video-player__wrap--{$ratio}";

        $preload = $lazy ? 'none' : 'metadata';
        ?>
        <div class="bt-video-player" data-bt-video='<?php echo esc_attr(wp_json_encode($config)); ?>'>
            <div class="bt-video-player__wrap<?php echo esc_attr($wrap_class); ?>">
                <?php echo $this->build_video_tag($config, $preload); ?>
                <?php if ($lazy): ?>
                    <div class="bt-video-player__poster<?php echo $poster ? '' : ' bt-video-player__poster--empty'; ?>">
                        <?php if ($poster): ?>
                            <img src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy" decoding="async">
                        <?php endif; ?>
                        <button class="bt-video-player__play" aria-label="<?php esc_attr_e('Lire la video', 'blacktenderscore'); ?>">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24"><path d="M8 5v14l11-7z"/></svg>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function resolve_video_url(array $s): string {
        switch ($s['video_source']) {
            case 'url':  return $s['video_url']['url'] ?? '';
            case 'file': return $s['video_file']['url'] ?? '';
            case 'acf':
                if (!function_exists('get_field')) return '';
                $v = get_field($s['video_acf_field']);
                return is_array($v) ? ($v['url'] ?? '') : (is_string($v) ? $v : '');
        }
        return '';
    }

    private function resolve_poster(array $s): string {
        switch ($s['poster_source']) {
            case 'featured': return get_the_post_thumbnail_url(get_the_ID(), 'large') ?: '';
            case 'custom':   return $s['poster_image']['url'] ?? '';
            case 'acf':
                if (!function_exists('get_field')) return '';
                $v = get_field($s['poster_acf_field']);
                return is_array($v) ? ($v['url'] ?? '') : (is_string($v) ? $v : '');
        }
        return '';
    }

    private function build_video_tag(array $cfg, string $preload): string {
        if ($cfg['type'] === 'file') {
            $a = sprintf('playsinline crossorigin="anonymous" preload="%s"', esc_attr($preload));
            if ($cfg['muted']) $a .= ' muted';
            if ($cfg['loop'])  $a .= ' loop';
            if ($cfg['poster']) $a .= ' poster="' . esc_attr($cfg['poster']) . '"';
            return sprintf('<video class="bt-video-player__video" src="%s" %s></video>', esc_attr($cfg['src']), $a);
        }
        if ($cfg['type'] === 'youtube' && !empty($cfg['videoId'])) {
            return sprintf('<div class="bt-video-player__video" data-plyr-provider="youtube" data-plyr-embed-id="%s"></div>', esc_attr($cfg['videoId']));
        }
        if ($cfg['type'] === 'vimeo' && !empty($cfg['videoId'])) {
            return sprintf('<div class="bt-video-player__video" data-plyr-provider="vimeo" data-plyr-embed-id="%s"></div>', esc_attr($cfg['videoId']));
        }
        return '';
    }

    private function detect_type(string $u): string {
        if (preg_match('/youtube\.com|youtu\.be/i', $u)) return 'youtube';
        if (preg_match('/vimeo\.com/i', $u)) return 'vimeo';
        return 'file';
    }

    private function extract_youtube_id(string $u): string {
        return preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $u, $m) ? $m[1] : '';
    }

    private function extract_vimeo_id(string $u): string {
        return preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $u, $m) ? $m[1] : '';
    }
}
