<?php
/**
 * Paso 3 — la fuente de Vínculos de Bloques.
 *
 * Las vinculaciones son las dueñas del MOMENTO DE RENDERIZADO: esta retrollamada
 * se ejecuta en PHP durante render_block, así que los bloques de párrafo y
 * encabezado del núcleo se convierten en el motor de plantillas de los datos de
 * la IA. Aquí no hay ninguna reactividad en el cliente — esa costura pertenece a
 * la API de Interactividad (ver el bloque tarjeta-receta).
 *
 * @package TarjetaRecetaIA
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'recetaia_registrar_fuente_vinculaciones' );

function recetaia_registrar_fuente_vinculaciones(): void {
	register_block_bindings_source(
		'recetaia/campos',
		array(
			'label'              => __( 'Campos de receta con IA', 'tarjeta-receta-ia' ),
			'get_value_callback' => 'recetaia_vinculaciones_obtener_valor',
			'uses_context'       => array( 'postId' ),
		)
	);
}

/**
 * Resuelve un atributo vinculado a su valor meta generado por la IA.
 *
 * @param array<string, mixed> $argumentos_fuente Argumentos del vínculo, p. ej. { "campo": "tiempo_preparacion" }.
 * @param WP_Block             $instancia_bloque  El bloque que se está renderizando.
 * @return string|null
 */
function recetaia_vinculaciones_obtener_valor( array $argumentos_fuente, WP_Block $instancia_bloque ): ?string {
	$id_entrada = $instancia_bloque->context['postId'] ?? get_the_ID();
	$campos     = recetaia_campos_vinculables();
	$campo      = $argumentos_fuente['campo'] ?? '';

	if ( ! $id_entrada || ! isset( $campos[ $campo ] ) ) {
		return null;
	}

	$valor = get_post_meta( $id_entrada, $campos[ $campo ], true );

	if ( '' === $valor || 0 === $valor ) {
		return __( 'Esperando el análisis de la IA…', 'tarjeta-receta-ia' );
	}

	return (string) $valor;
}
