<?php
/**
 * Hojas de estilo, scripts y fuentes.
 *
 * @package ParkinsonBahia
 */

declare(strict_types=1);

namespace DanielLucia\ParkinsonBahia\Theme;

defined( 'ABSPATH' ) || exit;

final class Assets {

	public function register(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'wp_head', [ $this, 'preload_fonts' ], 1 );
		add_action( 'wp_head', [ $this, 'reading_preferences_bootstrap' ], 2 );
	}

	public function enqueue(): void {
		wp_enqueue_style( 'pbc-main', $this->uri( 'assets/css/main.css' ), [], $this->version( 'assets/css/main.css' ) );

		// Estilos de noticias solo donde hay noticias.
		if ( is_home() || is_archive() || is_search() || is_singular( 'post' ) ) {
			wp_enqueue_style( 'pbc-blog', $this->uri( 'assets/css/blog.css' ), [ 'pbc-main' ], $this->version( 'assets/css/blog.css' ) );
		}

		// 404 y búsqueda: sugerencias, accesos rápidos y resultados.
		if ( is_404() || is_search() ) {
			wp_enqueue_style( 'pbc-search', $this->uri( 'assets/css/search.css' ), [ 'pbc-main' ], $this->version( 'assets/css/search.css' ) );
		}

		wp_enqueue_script(
			'pbc-main',
			$this->uri( 'assets/js/main.js' ),
			[],
			$this->version( 'assets/js/main.js' ),
			[
				'strategy'  => 'defer',
				'in_footer' => true,
			]
		);

		wp_localize_script(
			'pbc-main',
			'pbcTheme',
			[
				'menuOpen'   => __( 'Menú', 'parkinson-bahia' ),
				'menuClose'  => __( 'Cerrar', 'parkinson-bahia' ),
				'copyLink'   => __( 'Copiar enlace', 'parkinson-bahia' ),
				'linkCopied' => __( 'Enlace copiado', 'parkinson-bahia' ),
			]
		);
	}

	/**
	 * Precarga solo las dos fuentes que se ven al cargar la página.
	 */
	public function preload_fonts(): void {
		foreach ( [ 'atkinson-hyperlegible-next-400.woff2', 'young-serif-400.woff2' ] as $font ) {
			printf(
				'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
				esc_url( $this->uri( 'assets/fonts/' . $font ) )
			);
		}
	}

	/**
	 * Aplica el tamaño de texto y el contraste guardados antes del primer pintado,
	 * para que la página no cambie de aspecto después de cargar.
	 */
	public function reading_preferences_bootstrap(): void {
		$script = "(function(){try{var d=document.documentElement,f=localStorage.getItem('pbc_fs'),c=localStorage.getItem('pbc_contrast');d.classList.add('js');if(f==='1'||f==='2'){d.setAttribute('data-fs',f);}if(c==='1'){d.setAttribute('data-contrast','high');}}catch(e){document.documentElement.classList.add('js');}})();";
		wp_print_inline_script_tag( $script );
	}

	private function uri( string $path ): string {
		return get_theme_file_uri( $path );
	}

	private function version( string $path ): string {
		$file = get_theme_file_path( $path );
		return is_readable( $file ) ? (string) filemtime( $file ) : Theme::VERSION;
	}
}
