<?php
/**
 * Paso 2 (mitad de extracción) — convertir texto desordenado en datos con la
 * forma del esquema.
 *
 * Tres entradas, una sola función:
 *   - el botón de la barra lateral del editor (la ruta REST de más abajo),
 *   - el Cliente de IA de WordPress 7.0 (modo real),
 *   - cualquier agente conectado por MCP (la Habilidad del final).
 *
 * @package TarjetaRecetaIA
 */

defined( 'ABSPATH' ) || exit;

/**
 * Extrae datos estructurados de receta a partir de texto libre.
 *
 * @param string $contenido Texto de receta sin estructurar (el correo de la abuela).
 * @return array<string, mixed>|WP_Error Datos de receta que cumplen recetaia_esquema_receta().
 */
function recetaia_extraer_receta( string $contenido ) {
	if ( RECETAIA_USAR_SIMULACION ) {
		return recetaia_receta_simulada();
	}

	/*
	 * Sin el plugin del Cliente de IA activo la llamada de abajo sería un error
	 * fatal. Devolvemos un WP_Error para que el fallo recorra el mismo camino
	 * que cualquier otro y la ruta REST pueda recurrir a la receta de relleno.
	 */
	if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
		return new WP_Error(
			'recetaia_cliente_ia_ausente',
			__( 'El Cliente de IA de WordPress no está disponible.', 'tarjeta-receta-ia' ),
			array( 'status' => 501 )
		);
	}

	// El Cliente de IA de WordPress 7.0: agnóstico del proveedor, fluido y
	// —gracias a as_json_response()— atado a nuestro esquema. El esquema es el prompt.
	$json = wp_ai_client_prompt( $contenido )
		->using_system_instruction(
			'Extraes datos de recetas a partir de texto libre. Normaliza las cantidades vagas ' .
			'("un chorrito de aceite" => 2 cucharadas) y usa siempre unidades métricas cuando tenga sentido. ' .
			'Responde en español con un JSON que coincida exactamente con el esquema proporcionado.'
		)
		->as_json_response( recetaia_esquema_receta() )
		->generate_text();

	if ( is_wp_error( $json ) ) {
		return $json;
	}

	$receta = json_decode( (string) $json, true );

	if ( ! is_array( $receta ) ) {
		return new WP_Error(
			'recetaia_respuesta_invalida',
			__( 'La respuesta de la IA no era un JSON válido.', 'tarjeta-receta-ia' ),
			array( 'status' => 502 )
		);
	}

	$validacion = rest_validate_value_from_schema( $receta, recetaia_esquema_receta(), 'receta' );
	if ( is_wp_error( $validacion ) ) {
		return $validacion;
	}

	return $receta;
}

/**
 * Respuesta predefinida para que la demo funcione con el wifi del recinto caído.
 *
 * @return array<string, mixed>
 */
function recetaia_receta_simulada(): array {
	return array(
		'resumen'            => __( 'Una paella valenciana ahumada y con aroma a azafrán, recién salida de la cocina de la abuela — normalizada por la IA de "un chorrito de aceite" a números de verdad.', 'tarjeta-receta-ia' ),
		'tiempo_preparacion' => '20 min',
		'tiempo_coccion'     => '45 min',
		'raciones'           => 4,
		'dificultad'         => 'Intermedia',
		'ingredientes'       => array(
			array(
				'cantidad' => 320,
				'unidad'   => 'g',
				'nombre'   => 'arroz bomba',
			),
			array(
				'cantidad' => 1,
				'unidad'   => 'l',
				'nombre'   => 'caldo de pollo',
			),
			array(
				'cantidad' => 400,
				'unidad'   => 'g',
				'nombre'   => 'muslos de pollo troceados',
			),
			array(
				'cantidad' => 200,
				'unidad'   => 'g',
				'nombre'   => 'judías verdes',
			),
			array(
				'cantidad' => 2,
				'unidad'   => 'cda',
				'nombre'   => 'aceite de oliva (la abuela dijo "un chorrito")',
			),
			array(
				'cantidad' => 1,
				'unidad'   => 'cdta',
				'nombre'   => 'pimentón dulce',
			),
			array(
				'cantidad' => 0.5,
				'unidad'   => 'g',
				'nombre'   => 'hebras de azafrán',
			),
			array(
				'cantidad' => 2,
				'unidad'   => '',
				'nombre'   => 'tomates maduros rallados',
			),
			array(
				'cantidad' => 1,
				'unidad'   => '',
				'nombre'   => 'limón en gajos',
			),
		),
	);
}

/**
 * La ruta REST que llama el botón de la barra lateral del editor. Solo extrae —
 * el editor escribe el resultado en el meta con editPost(), así que viaja por el
 * flujo de guardado normal.
 */
add_action( 'rest_api_init', 'recetaia_registrar_ruta_rest' );

function recetaia_registrar_ruta_rest(): void {
	register_rest_route(
		'recetaia/v1',
		'/extraer',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'permission_callback' => static function (): bool {
				return current_user_can( 'edit_posts' );
			},
			'args'                => array(
				'contenido' => array(
					'type'     => 'string',
					'required' => true,
				),
			),
			'callback'            => static function ( WP_REST_Request $peticion ) {
				$receta = recetaia_extraer_receta( (string) $peticion['contenido'] );

				/*
				 * Red de seguridad de la demo: si la extracción falla —sin proveedor
				 * configurado, error del proveedor, JSON que no cumple el esquema— el
				 * botón del editor no se queda a medias. Devolvemos la paella
				 * predefinida marcada como simulada y el editor avisa de que son datos
				 * de relleno. La Habilidad (MCP) sigue recibiendo el WP_Error de
				 * verdad: un agente no debe tragarse datos inventados en silencio.
				 */
				if ( is_wp_error( $receta ) ) {
					$simulada             = recetaia_receta_simulada();
					$simulada['simulada'] = true;
					$simulada['motivo']   = $receta->get_error_message();

					return rest_ensure_response( $simulada );
				}

				return rest_ensure_response( $receta );
			},
		)
	);
}

/**
 * El broche final: la misma extracción, registrada como Habilidad. A través del
 * Adaptador MCP, cualquier agente (Claude, etc.) puede descubrirla y llamarla —
 * sin integración a medida, porque los esquemas de entrada y salida SON la
 * documentación.
 */
add_action( 'wp_abilities_api_categories_init', 'recetaia_registrar_categoria_habilidad' );

function recetaia_registrar_categoria_habilidad(): void {
	wp_register_ability_category(
		'recetas',
		array(
			'label'       => __( 'Recetas', 'tarjeta-receta-ia' ),
			'description' => __( 'Habilidades que trabajan con contenido de recetas estructurado.', 'tarjeta-receta-ia' ),
		)
	);
}

add_action( 'wp_abilities_api_init', 'recetaia_registrar_habilidad' );

function recetaia_registrar_habilidad(): void {
	wp_register_ability(
		'tarjeta-receta-ia/extraer-receta',
		array(
			'label'               => __( 'Extraer receta', 'tarjeta-receta-ia' ),
			'description'         => __( 'Extrae datos estructurados de receta (resumen, tiempos, raciones, dificultad, ingredientes) a partir de texto libre.', 'tarjeta-receta-ia' ),
			'category'            => 'recetas',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'contenido' => array(
						'type'        => 'string',
						'description' => __( 'El texto de la receta sin estructurar.', 'tarjeta-receta-ia' ),
					),
				),
				'required'             => array( 'contenido' ),
				'additionalProperties' => false,
			),
			'output_schema'       => recetaia_esquema_receta(),
			'execute_callback'    => static function ( array $entrada ) {
				return recetaia_extraer_receta( $entrada['contenido'] ?? '' );
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
