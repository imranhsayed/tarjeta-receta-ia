<?php
/**
 * El contrato.
 *
 * Este único esquema JSON se le entrega al Cliente de IA como la forma de
 * respuesta obligatoria Y refleja el registro del post meta. La IA, la base de
 * datos y la fuente de vinculaciones están de acuerdo en él — ese acuerdo es lo
 * que hace que la salida de la IA sea verificable en vez de "el HTML que al
 * modelo le apeteciera".
 *
 * @package TarjetaRecetaIA
 */

defined( 'ABSPATH' ) || exit;

/**
 * Esquema JSON que describe una receta estructurada.
 *
 * @return array<string, mixed>
 */
function recetaia_esquema_receta(): array {
	return array(
		'type'                 => 'object',
		'properties'           => array(
			'resumen'              => array(
				'type'        => 'string',
				'description' => 'Resumen apetitoso del plato en una sola frase.',
			),
			'tiempo_preparacion'   => array(
				'type'        => 'string',
				'description' => 'Tiempo de preparación legible para personas, p. ej. "20 min".',
			),
			'tiempo_coccion'       => array(
				'type'        => 'string',
				'description' => 'Tiempo de cocción legible para personas, p. ej. "45 min".',
			),
			'raciones'             => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => 'Número de raciones para el que están escritas las cantidades de los ingredientes.',
			),
			'dificultad'           => array(
				'type' => 'string',
				'enum' => array( 'Fácil', 'Intermedia', 'Avanzada' ),
			),
			'ingredientes'         => array(
				'type'  => 'array',
				'items' => array(
					'type'                 => 'object',
					'properties'           => array(
						'cantidad' => array(
							'type'        => 'number',
							'description' => 'Cantidad numérica, normalizada (nada de "un chorrito").',
						),
						'unidad'   => array(
							'type'        => 'string',
							'description' => 'Unidad como g, ml o cda. Cadena vacía para elementos contables.',
						),
						'nombre'   => array( 'type' => 'string' ),
					),
					'required'             => array( 'cantidad', 'unidad', 'nombre' ),
					'additionalProperties' => false,
				),
			),
		),
		'required'             => array( 'resumen', 'tiempo_preparacion', 'tiempo_coccion', 'raciones', 'dificultad', 'ingredientes' ),
		'additionalProperties' => false,
	);
}

/**
 * Los campos escalares de la receta expuestos a los Vínculos de Bloques, como
 * campo del vínculo => clave meta. Los ingredientes faltan a propósito: los
 * arrays estructurados pertenecen al bloque de la API de Interactividad, no a
 * vinculaciones de atributos de tipo cadena.
 *
 * @return array<string, string>
 */
function recetaia_campos_vinculables(): array {
	return array(
		'resumen'            => 'recetaia_resumen',
		'tiempo_preparacion' => 'recetaia_tiempo_preparacion',
		'tiempo_coccion'     => 'recetaia_tiempo_coccion',
		'raciones'           => 'recetaia_raciones',
		'dificultad'         => 'recetaia_dificultad',
	);
}
