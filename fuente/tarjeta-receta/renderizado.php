<?php
/**
 * Paso 4 — renderizado en el servidor de la tarjeta de receta interactiva.
 *
 * El mismo meta que leen las vinculaciones, pero aquí siembra el contexto de la
 * API de Interactividad. El HTML inicial está completo (cantidades incluidas),
 * así que la tarjeta se puede leer antes de la hidratación y sin JavaScript. Las
 * directivas toman el relevo después.
 *
 * @package TarjetaRecetaIA
 */

$recetaia_id_entrada   = $block->context['postId'] ?? get_the_ID();
$recetaia_ingredientes = get_post_meta( $recetaia_id_entrada, 'recetaia_ingredientes', true );
$recetaia_raciones     = max( 1, (int) get_post_meta( $recetaia_id_entrada, 'recetaia_raciones', true ) );

if ( empty( $recetaia_ingredientes ) || ! is_array( $recetaia_ingredientes ) ) {
	?>
	<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		<p class="recetaia-vacio"><?php esc_html_e( 'Esperando el análisis de la IA — extrae primero la receta en el editor.', 'tarjeta-receta-ia' ); ?></p>
	</div>
	<?php
	return;
}

/*
 * La mitad servidor del estado derivado. El getter del cliente en vista.js
 * recalcula cantidadEscalada tras la hidratación; este cierre calcula el mismo
 * valor mientras PHP procesa las directivas, de modo que el HTML inicial (vista
 * previa del editor, frontend sin JS) ya muestra las cantidades correctas.
 */
wp_interactivity_state(
	'tarjeta-receta-ia',
	array(
		'cantidadEscalada' => static function (): string {
			$contexto = wp_interactivity_get_context();
			$escalada = $contexto['cantidad'] * $contexto['raciones'] / max( 1, $contexto['racionesBase'] );

			return floor( $escalada ) == $escalada // phpcs:ignore Universal.Operators.StrictComparisons
				? (string) (int) $escalada
				: number_format( $escalada, 1 );
		},
	)
);
?>
<div
	<?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	data-wp-interactive="tarjeta-receta-ia"
	<?php
	echo wp_interactivity_data_wp_context( // phpcs:ignore WordPress.Security.EscapeOutput
		array(
			'raciones'     => $recetaia_raciones,
			'racionesBase' => $recetaia_raciones,
		)
	);
	?>
>
	<div class="recetaia-raciones">
		<span class="recetaia-raciones__etiqueta"><?php esc_html_e( 'Raciones', 'tarjeta-receta-ia' ); ?></span>
		<button
			class="recetaia-raciones__boton"
			data-wp-on--click="actions.disminuirRaciones"
			aria-label="<?php esc_attr_e( 'Disminuir raciones', 'tarjeta-receta-ia' ); ?>"
		>−</button>
		<span class="recetaia-raciones__contador" data-wp-text="context.raciones"><?php echo esc_html( $recetaia_raciones ); ?></span>
		<button
			class="recetaia-raciones__boton"
			data-wp-on--click="actions.aumentarRaciones"
			aria-label="<?php esc_attr_e( 'Aumentar raciones', 'tarjeta-receta-ia' ); ?>"
		>+</button>
	</div>

	<ul class="recetaia-ingredientes">
		<?php foreach ( $recetaia_ingredientes as $recetaia_elemento ) : ?>
			<li
				class="recetaia-ingredientes__elemento"
				<?php
				echo wp_interactivity_data_wp_context( // phpcs:ignore WordPress.Security.EscapeOutput
					array(
						'cantidad' => (float) $recetaia_elemento['cantidad'],
						'marcado'  => false,
					)
				);
				?>
				data-wp-class--esta-marcado="context.marcado"
				data-wp-on--click="actions.alternarIngrediente"
			>
				<span class="recetaia-ingredientes__cantidad" data-wp-text="state.cantidadEscalada"><?php echo esc_html( $recetaia_elemento['cantidad'] ); ?></span>
				<span class="recetaia-ingredientes__unidad"><?php echo esc_html( $recetaia_elemento['unidad'] ); ?></span>
				<span class="recetaia-ingredientes__nombre"><?php echo esc_html( $recetaia_elemento['nombre'] ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
