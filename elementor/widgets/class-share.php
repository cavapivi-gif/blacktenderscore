<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;
use Elementor\Icons_Manager;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Partager / Demander à l'IA.
 *
 * Section 1 — Réseaux sociaux + partage natif :
 *   Copy link, Web Share API, X/Twitter, Facebook, WhatsApp, LinkedIn, Email
 *
 * Section 2 — IA (Claude, ChatGPT, Gemini) avec logos SVG inline.
 *
 * JS : bt-elementor.js (handler 'bt-share')
 */
class Share extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-share',
            'title'    => 'BT — Partager',
            'icon'     => 'eicon-share',
            'keywords' => ['partager', 'share', 'ia', 'claude', 'chatgpt', 'gemini', 'facebook', 'twitter', 'x', 'whatsapp', 'bt'],
            'js'       => ['bt-elementor'],
        ];
    }

    // ── SVG logos des IA ─────────────────────────────────────────────────────

    private const LOGO_CLAUDE = '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="bt-share__ai-logo"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm-.5 5.5c.276 0 .5.224.5.5v4.086l2.793-2.793a.5.5 0 1 1 .707.707L12.707 12.5l2.793 2.793a.5.5 0 1 1-.707.707L12 13.207V17.5a.5.5 0 1 1-1 0v-4.293l-2.793 2.793a.5.5 0 1 1-.707-.707L10.293 12.5 7.5 9.707a.5.5 0 1 1 .707-.707L11 11.793V8a.5.5 0 0 1 .5-.5z"/></svg>';

    private const LOGO_CHATGPT = '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="bt-share__ai-logo"><path d="M22.282 9.821a5.985 5.985 0 0 0-.516-4.91 6.046 6.046 0 0 0-6.51-2.9A6.065 6.065 0 0 0 4.981 4.18a5.985 5.985 0 0 0-3.998 2.9 6.046 6.046 0 0 0 .743 7.097 5.98 5.98 0 0 0 .51 4.911 6.051 6.051 0 0 0 6.515 2.9A5.985 5.985 0 0 0 13.26 24a6.056 6.056 0 0 0 5.772-4.206 5.99 5.99 0 0 0 3.997-2.9 6.056 6.056 0 0 0-.747-7.073zM13.26 22.43a4.476 4.476 0 0 1-2.876-1.04l.141-.081 4.779-2.758a.795.795 0 0 0 .392-.681v-6.737l2.02 1.168a.071.071 0 0 1 .038.052v5.583a4.504 4.504 0 0 1-4.494 4.494zM3.6 18.304a4.47 4.47 0 0 1-.535-3.014l.142.085 4.783 2.759a.771.771 0 0 0 .78 0l5.843-3.369v2.332a.08.08 0 0 1-.032.067L9.73 19.931a4.5 4.5 0 0 1-6.13-1.627zm-1.156-10.09a4.475 4.475 0 0 1 2.339-1.97V12.1a.766.766 0 0 0 .388.676l5.815 3.355-2.02 1.168a.076.076 0 0 1-.071 0L4.048 14.53A4.5 4.5 0 0 1 2.444 8.214zm16.556 3.862L13.15 8.71l2.022-1.166a.076.076 0 0 1 .071 0l4.847 2.797a4.5 4.5 0 0 1-.676 8.123v-5.854a.796.796 0 0 0-.414-.678zm2.009-3.023l-.141-.085-4.774-2.782a.776.776 0 0 0-.785 0L9.409 9.23V6.897a.066.066 0 0 1 .028-.061l4.846-2.797a4.5 4.5 0 0 1 6.68 4.66zm-12.64 4.135l-2.02-1.164a.08.08 0 0 1-.038-.057V6.075a4.5 4.5 0 0 1 7.375-3.453l-.142.08L8.565 5.46a.795.795 0 0 0-.394.68zm1.097-2.365l2.602-1.5 2.603 1.5v2.999l-2.597 1.5-2.603-1.5Z"/></svg>';

    private const LOGO_GEMINI = '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="bt-share__ai-logo"><path d="M12 24A14.304 14.304 0 0 0 0 12 14.304 14.304 0 0 0 12 0a14.304 14.304 0 0 0 12 12 14.304 14.304 0 0 0-12 12"/></svg>';

    // ── SVG icône partage ─────────────────────────────────────────────────────

    private const ICON_SHARE = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="bt-share__btn-icon"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>';

    // ── Icônes réseaux sociaux ────────────────────────────────────────────────

    private const SOCIAL_ICONS = [
        'copy'      => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>',
        'x'         => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.741l7.732-8.835L1.254 2.25H8.08l4.261 5.636 5.903-5.636Zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
        'facebook'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        'whatsapp'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>',
        'linkedin'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
        'email'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
    ];

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Bouton de partage principal ───────────────────────────────────
        $this->start_controls_section('section_share_btn', [
            'label' => __('Bouton de partage', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('share_icon', [
            'label'   => __('Icône', 'blacktenderscore'),
            'type'    => Controls_Manager::ICONS,
            'default' => ['value' => '', 'library' => ''],
            'skin'    => 'inline',
        ]);

        $this->add_control('share_label', [
            'label'   => __('Label', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('Partager cette page', 'blacktenderscore'),
        ]);

        $this->add_control('copied_label', [
            'label'       => __('Message après copie (fallback)', 'blacktenderscore'),
            'description' => __('Affiché quand le Web Share API n\'est pas disponible.', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('Lien copié !', 'blacktenderscore'),
        ]);

        $this->add_responsive_control('share_btn_align', [
            'label'     => __('Alignement', 'blacktenderscore'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'flex-start' => ['title' => __('Gauche',  'blacktenderscore'), 'icon' => 'eicon-text-align-left'],
                'center'     => ['title' => __('Centre',  'blacktenderscore'), 'icon' => 'eicon-text-align-center'],
                'flex-end'   => ['title' => __('Droite',  'blacktenderscore'), 'icon' => 'eicon-text-align-right'],
            ],
            'selectors' => ['{{WRAPPER}} .bt-share__top' => 'justify-content: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // ── Réseaux sociaux ───────────────────────────────────────────────
        $this->start_controls_section('section_social', [
            'label' => __('Réseaux sociaux', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_social', [
            'label'        => __('Afficher les boutons réseaux', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('social_title', [
            'label'     => __('Titre section', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Partager sur', 'blacktenderscore'),
            'condition' => ['show_social' => 'yes'],
        ]);

        foreach ([
            'x'        => ['X / Twitter', 'yes'],
            'facebook' => ['Facebook', 'yes'],
            'whatsapp' => ['WhatsApp', 'yes'],
            'linkedin' => ['LinkedIn', ''],
            'email'    => ['Email', ''],
        ] as $net => [$netLabel, $default]) {
            $this->add_control("show_{$net}", [
                'label'        => sprintf(__('Afficher %s', 'blacktenderscore'), $netLabel),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => $default,
                'condition'    => ['show_social' => 'yes'],
            ]);
        }

        $this->end_controls_section();

        // ── Demander à l'IA ───────────────────────────────────────────────
        $this->start_controls_section('section_ai', [
            'label' => __('Demander à l\'IA', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_ai', [
            'label'        => __('Afficher les liens IA', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('ai_title', [
            'label'     => __('Titre section', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Demander à l\'IA', 'blacktenderscore'),
            'condition' => ['show_ai' => 'yes'],
        ]);

        $this->add_control('ai_prompt_prefix', [
            'label'       => __('Début du prompt', 'blacktenderscore'),
            'description' => __('Le titre + URL de la page seront ajoutés automatiquement.', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'label_block' => true,
            'default'     => __('Résume et présente-moi cette page :', 'blacktenderscore'),
            'condition'   => ['show_ai' => 'yes'],
        ]);

        foreach ([
            'claude'  => ['Claude',  'yes', '#b44c1d'],
            'chatgpt' => ['ChatGPT', 'yes', '#10a37f'],
            'gemini'  => ['Gemini',  '',    '#1a73e8'],
        ] as $ai => [$aiLabel, $default, $color]) {
            $this->add_control("show_{$ai}", [
                'label'        => sprintf(__('Afficher %s', 'blacktenderscore'), $aiLabel),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => $default,
                'condition'    => ['show_ai' => 'yes'],
            ]);
            $this->add_control("{$ai}_label", [
                'label'     => sprintf(__('Label %s', 'blacktenderscore'), $aiLabel),
                'type'      => Controls_Manager::TEXT,
                'default'   => sprintf(__('Demander à %s', 'blacktenderscore'), $aiLabel),
                'condition' => ['show_ai' => 'yes', "show_{$ai}" => 'yes'],
            ]);
        }

        $this->end_controls_section();

        // ── Style — Bouton principal ──────────────────────────────────────
        $this->register_item_3state_style(
            'share_btn',
            __('Style — Bouton partage', 'blacktenderscore'),
            '{{WRAPPER}} .bt-share__btn'
        );
        $this->register_typography_section(
            'share_btn_label',
            __('Style — Texte bouton partage', 'blacktenderscore'),
            '{{WRAPPER}} .bt-share__btn'
        );

        // ── Style — Titres de section ─────────────────────────────────────
        $this->register_typography_section(
            'section_titles',
            __('Style — Titres de section', 'blacktenderscore'),
            '{{WRAPPER}} .bt-share__section-title'
        );

        // ── Style — Boutons réseaux sociaux ───────────────────────────────
        $this->register_item_3state_style(
            'social_btn',
            __('Style — Boutons réseaux sociaux', 'blacktenderscore'),
            '{{WRAPPER}} .bt-share__social-link',
            null,
            null,
            ['show_social' => 'yes']
        );
        $this->register_typography_section(
            'social_label',
            __('Style — Labels réseaux sociaux', 'blacktenderscore'),
            '{{WRAPPER}} .bt-share__social-label',
            [],
            [],
            ['show_social' => 'yes']
        );

        // ── Style — Liens IA ──────────────────────────────────────────────
        $this->register_item_3state_style(
            'ai_link',
            __('Style — Liens IA', 'blacktenderscore'),
            '{{WRAPPER}} .bt-share__ai-link',
            null,
            null,
            ['show_ai' => 'yes']
        );
        $this->register_typography_section(
            'ai_label',
            __('Style — Labels IA', 'blacktenderscore'),
            '{{WRAPPER}} .bt-share__ai-label',
            [],
            [],
            ['show_ai' => 'yes']
        );
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s = $this->get_settings_for_display();

        $page_title  = get_the_title();
        $page_url    = get_permalink();
        $prompt      = esc_attr(($s['ai_prompt_prefix'] ?: 'Résume et présente-moi cette page :') . ' ' . $page_title . ' — ' . $page_url);
        $prompt_enc  = rawurlencode(($s['ai_prompt_prefix'] ?: 'Résume et présente-moi cette page :') . ' ' . $page_title . ' — ' . $page_url);

        $share_label  = $s['share_label']  ?: __('Partager cette page', 'blacktenderscore');
        $copied_label = esc_attr($s['copied_label'] ?: __('Lien copié !', 'blacktenderscore'));

        echo '<div class="bt-share">';

        // ── Bouton de partage principal ───────────────────────────────────
        echo '<div class="bt-share__top">';
        echo '<button type="button" class="bt-share__btn"'
            . ' data-bt-share'
            . ' data-bt-url="' . esc_attr($page_url) . '"'
            . ' data-bt-title="' . esc_attr($page_title) . '"'
            . ' data-bt-copied="' . $copied_label . '"'
            . '>';

        // Icône Elementor (si définie) ou icône SVG native
        if (!empty($s['share_icon']['value'])) {
            Icons_Manager::render_icon($s['share_icon'], ['aria-hidden' => 'true', 'class' => 'bt-share__btn-icon']);
        } else {
            echo self::ICON_SHARE;
        }

        echo '<span class="bt-share__btn-label">' . esc_html($share_label) . '</span>';
        echo '</button>';
        echo '</div>';

        // ── Réseaux sociaux ───────────────────────────────────────────────
        if ($s['show_social'] === 'yes') {
            $socials = $this->build_social_links($s, $page_url, $page_title);

            if (!empty($socials)) {
                echo '<div class="bt-share__section">';

                if (!empty($s['social_title'])) {
                    echo '<p class="bt-share__section-title">' . esc_html($s['social_title']) . '</p>';
                }

                echo '<div class="bt-share__social-links">';
                foreach ($socials as $net => $data) {
                    $tag   = $data['js'] ? 'button type="button"' : 'a target="_blank" rel="noopener noreferrer"';
                    $tag_c = $data['js'] ? 'button' : 'a';
                    $href  = $data['js'] ? '' : ' href="' . esc_url($data['url']) . '"';
                    $dattr = !empty($data['data']) ? ' ' . $data['data'] : '';

                    echo "<{$tag} class=\"bt-share__social-link bt-share__social-link--{$net}\"{$href}{$dattr}>";
                    echo self::SOCIAL_ICONS[$net] ?? '';
                    echo '<span class="bt-share__social-label">' . esc_html($data['label']) . '</span>';
                    echo "</{$tag_c}>";
                }
                echo '</div>';
                echo '</div>';
            }
        }

        // ── Liens IA ──────────────────────────────────────────────────────
        if ($s['show_ai'] === 'yes') {
            $ai_links = [];

            if ($s['show_claude'] === 'yes') {
                $ai_links['claude'] = [
                    'url'   => 'https://claude.ai/new?q=' . $prompt_enc,
                    'label' => $s['claude_label'] ?: __('Demander à Claude', 'blacktenderscore'),
                    'logo'  => self::LOGO_CLAUDE,
                ];
            }
            if ($s['show_chatgpt'] === 'yes') {
                $ai_links['chatgpt'] = [
                    'url'   => 'https://chatgpt.com/?q=' . $prompt_enc,
                    'label' => $s['chatgpt_label'] ?: __('Demander à ChatGPT', 'blacktenderscore'),
                    'logo'  => self::LOGO_CHATGPT,
                ];
            }
            if ($s['show_gemini'] === 'yes') {
                $ai_links['gemini'] = [
                    'url'   => 'https://gemini.google.com/app?text=' . $prompt_enc,
                    'label' => $s['gemini_label'] ?: __('Demander à Gemini', 'blacktenderscore'),
                    'logo'  => self::LOGO_GEMINI,
                ];
            }

            if (!empty($ai_links)) {
                echo '<div class="bt-share__section">';

                if (!empty($s['ai_title'])) {
                    echo '<p class="bt-share__section-title">' . esc_html($s['ai_title']) . '</p>';
                }

                echo '<div class="bt-share__ai-links">';
                foreach ($ai_links as $key => $ai) {
                    echo '<a'
                        . ' href="' . esc_url($ai['url']) . '"'
                        . ' class="bt-share__ai-link bt-share__ai-link--' . esc_attr($key) . '"'
                        . ' target="_blank"'
                        . ' rel="noopener noreferrer"'
                        . '>';
                    echo $ai['logo'];
                    echo '<span class="bt-share__ai-label">' . esc_html($ai['label']) . '</span>';
                    echo '</a>';
                }
                echo '</div>';
                echo '</div>';
            }
        }

        echo '</div>'; // .bt-share
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function build_social_links(array $s, string $url, string $title): array {
        $enc_url   = rawurlencode($url);
        $enc_title = rawurlencode($title);
        $links     = [];

        if ($s['show_x'] === 'yes') {
            $links['x'] = [
                'url'   => "https://x.com/intent/tweet?url={$enc_url}&text={$enc_title}",
                'label' => 'X / Twitter',
                'js'    => false,
            ];
        }
        if ($s['show_facebook'] === 'yes') {
            $links['facebook'] = [
                'url'   => "https://www.facebook.com/sharer/sharer.php?u={$enc_url}",
                'label' => 'Facebook',
                'js'    => false,
            ];
        }
        if ($s['show_whatsapp'] === 'yes') {
            $links['whatsapp'] = [
                'url'   => "https://wa.me/?text={$enc_title}%20{$enc_url}",
                'label' => 'WhatsApp',
                'js'    => false,
            ];
        }
        if ($s['show_linkedin'] === 'yes') {
            $links['linkedin'] = [
                'url'   => "https://www.linkedin.com/sharing/share-offsite/?url={$enc_url}",
                'label' => 'LinkedIn',
                'js'    => false,
            ];
        }
        if ($s['show_email'] === 'yes') {
            $links['email'] = [
                'url'   => "mailto:?subject={$enc_title}&body={$enc_url}",
                'label' => 'Email',
                'js'    => false,
            ];
        }

        return $links;
    }
}
