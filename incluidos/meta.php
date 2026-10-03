<?php
/**
 * Paso 2 (mitad de almacenamiento) — registrar el post meta en el que escribe la IA.
 *
 * `show_in_rest` importa dos veces: el editor de bloques lee y escribe estos
 * campos a través de la API REST, y la fuente de vinculaciones del lado del
 * cliente los lee del registro de entidad editado.
 *
 * @package TarjetaRecetaIA
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'recetaia_registrar_meta' );

function recetaia_registrar_meta(): void {
	$autorizacion = static function (): bool {
		return current_user_can( 'edit_posts' );
	};

	foreach ( array( 'recetaia_resumen', 'recetaia_tiempo_preparacion', 'recetaia_tiempo_coccion', 'recetaia_dificultad' ) as $clave ) {
		register_post_meta(
			'post',
			$clave,
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => $autorizacion,
			)
		);
	}

	register_post_meta(
		'post',
		'recetaia_raciones',
		array(
			'show_in_rest'      => true,
			'single'            => true,
			'type'              => 'integer',
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'auth_callback'     => $autorizacion,
		)
	);

	register_post_meta(
		'post',
		'recetaia_ingredientes',
		array(
			'show_in_rest'  => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'properties'           => array(
							'cantidad' => array( 'type' => 'number' ),
							'unidad'   => array( 'type' => 'string' ),
							'nombre'   => array( 'type' => 'string' ),
						),
						'required'             => array( 'cantidad', 'unidad', 'nombre' ),
						'additionalProperties' => false,
					),
				),
			),
			'single'        => true,
			'type'          => 'array',
			'default'       => array(),
			'auth_callback' => $autorizacion,
		)
	);
}
