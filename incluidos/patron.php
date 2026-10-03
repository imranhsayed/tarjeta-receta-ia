<?php
/**
 * Paso 3 (mitad de la demo) — un patrón de bloques del NÚCLEO vinculados al meta de la IA.
 *
 * Fíjate en lo que NO hay aquí: ningún bloque de presentación a medida, ninguna
 * retrollamada de renderizado para maquetar. Encabezados y párrafos del núcleo,
 * conectados a los datos mediante metadata.bindings. El único bloque propio es
 * el interactivo.
 *
 * @package TarjetaRecetaIA
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'recetaia_registrar_patron' );

function recetaia_registrar_patron(): void {
	$vincular = static function ( string $campo ): string {
		return wp_json_encode(
			array(
				'metadata' => array(
					'bindings' => array(
						'content' => array(
							'source' => 'recetaia/campos',
							'args'   => array( 'campo' => $campo ),
						),
					),
				),
			)
		);
	};

	$elemento_meta = static function ( string $etiqueta, string $campo ) use ( $vincular ): string {
		return <<<HTML
<!-- wp:group {"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"fontSize":"small"} -->
<p><strong>{$etiqueta}</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {$vincular($campo)} -->
<p>—</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
HTML;
	};

	$contenido = '<!-- wp:group {"metadata":{"name":"Tarjeta de Receta con IA"}} -->
<div class="wp-block-group"><!-- wp:paragraph ' . $vincular( 'resumen' ) . ' -->
<p>' . esc_html__( 'Esperando el análisis de la IA…', 'tarjeta-receta-ia' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group">'
		. $elemento_meta( esc_html__( '⏱️ Preparación', 'tarjeta-receta-ia' ), 'tiempo_preparacion' )
		. $elemento_meta( esc_html__( '🔥 Cocción', 'tarjeta-receta-ia' ), 'tiempo_coccion' )
		. $elemento_meta( esc_html__( '🍽️ Raciones', 'tarjeta-receta-ia' ), 'raciones' )
		. $elemento_meta( esc_html__( '📈 Dificultad', 'tarjeta-receta-ia' ), 'dificultad' ) .
'</div>
<!-- /wp:group -->

<!-- wp:recetaia/tarjeta-receta /--></div>
<!-- /wp:group -->';

	register_block_pattern(
		'tarjeta-receta-ia/diseno-receta',
		array(
			'title'       => __( 'Tarjeta de Receta con IA', 'tarjeta-receta-ia' ),
			'description' => __( 'Bloques del núcleo vinculados al meta de receta extraído por la IA, más la tarjeta interactiva de ingredientes.', 'tarjeta-receta-ia' ),
			'categories'  => array( 'text' ),
			'content'     => $contenido,
		)
	);
}
