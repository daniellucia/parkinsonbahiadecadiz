<?php
/**
 * Caja «llms.txt» en la edición de cada contenido: excluirlo o escribir su descripción.
 *
 * @package ParkinsonLlmsTxt
 */

declare(strict_types=1);

namespace DanielLucia\LlmsTxt;

use WP_Post;

defined( 'ABSPATH' ) || exit;

final class PostFields {

	private const NONCE = 'pllms_post_fields';

	public function __construct( private readonly Settings $settings ) {}

	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post', [ $this, 'save' ], 5, 2 );
	}

	public function add_meta_box( string $post_type ): void {
		if ( ! array_key_exists( $post_type, $this->settings->post_types() ) ) {
			return;
		}

		add_meta_box(
			'pllms-post-fields',
			__( 'llms.txt', 'parkinson-llms-txt' ),
			[ $this, 'render' ],
			$post_type,
			'side',
			'low'
		);
	}

	public function render( WP_Post $post ): void {
		wp_nonce_field( self::NONCE, self::NONCE . '_nonce' );
		?>
		<p>
			<label>
				<input type="checkbox" name="pllms_exclude" value="1" <?php checked( (bool) get_post_meta( $post->ID, Settings::META_SKIP, true ) ); ?>>
				<?php esc_html_e( 'No incluir en llms.txt', 'parkinson-llms-txt' ); ?>
			</label>
		</p>
		<p>
			<label for="pllms-description"><?php esc_html_e( 'Descripción para llms.txt', 'parkinson-llms-txt' ); ?></label>
			<textarea class="widefat" rows="3" id="pllms-description" name="pllms_description"><?php echo esc_textarea( (string) get_post_meta( $post->ID, Settings::META_DESCR, true ) ); ?></textarea>
			<span class="description"><?php esc_html_e( 'Opcional. Una frase que diga qué encontrará quien abra esta página. Si lo dejas vacío se usa el extracto o el principio del texto.', 'parkinson-llms-txt' ); ?></span>
		</p>
		<?php
	}

	public function save( int $post_id, WP_Post $post ): void {
		$nonce = isset( $_POST[ self::NONCE . '_nonce' ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE . '_nonce' ] ) ) : '';

		if (
			! wp_verify_nonce( $nonce, self::NONCE )
			|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| wp_is_post_revision( $post_id )
			|| ! current_user_can( 'edit_post', $post_id )
		) {
			return;
		}

		if ( ! empty( $_POST['pllms_exclude'] ) ) {
			update_post_meta( $post_id, Settings::META_SKIP, 1 );
		} else {
			delete_post_meta( $post_id, Settings::META_SKIP );
		}

		$description = isset( $_POST['pllms_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['pllms_description'] ) ) : '';

		if ( '' !== $description ) {
			update_post_meta( $post_id, Settings::META_DESCR, $description );
		} else {
			delete_post_meta( $post_id, Settings::META_DESCR );
		}
	}
}
