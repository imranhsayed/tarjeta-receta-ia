<?php
/**
 * Plugin Name:       Tarjeta de Receta con IA
 * Description:       Demo de WordCamp — la IA estructura el contenido en post meta, los Vínculos de Bloques lo renderizan con bloques del núcleo y la API de Interactividad lo hace reactivo en el frontend.
 * Version:           1.0.0
 * Requires at least: 7.0
 * Requires PHP:      7.4
 * Author:            Imran Sayed
 * License:           GPL-2.0-or-later
 * Text Domain:       tarjeta-receta-ia
 *
 * @package TarjetaRecetaIA
 */

defined( 'ABSPATH' ) || exit;

define( 'RECETAIA_DIR', __DIR__ );

/*
 * Interruptor de seguridad para la demo. Cuando es true (el valor por defecto),
 * la extracción con IA devuelve una paella predefinida en vez de llamar a un
 * proveedor — toda la charla funciona sin conexión. Ponlo a false en
 * wp-config.php para usar de verdad el Cliente de IA de WordPress 7.0:
 *
 *     define( 'RECETAIA_USAR_SIMULACION', false );
 */
if ( ! defined( 'RECETAIA_USAR_SIMULACION' ) ) {
	define( 'RECETAIA_USAR_SIMULACION', false );
}

// La demo, en el orden de la charla. El esquema de esquema.php es el contrato
// que une los tres pilares.
require_once RECETAIA_DIR . '/incluidos/esquema.php';       // El contrato: un único esquema JSON compartido por la IA y el meta.
require_once RECETAIA_DIR . '/incluidos/meta.php';          // Paso 2 — la salida de la IA aterriza en post meta registrado.
require_once RECETAIA_DIR . '/incluidos/ia.php';            // Paso 2 — extracción con el Cliente de IA + ruta REST + Habilidad.
require_once RECETAIA_DIR . '/incluidos/vinculaciones.php'; // Paso 3 — fuente de Vínculos de Bloques (momento de renderizado).
require_once RECETAIA_DIR . '/incluidos/patron.php';        // Paso 3 — patrón de bloques del núcleo vinculados.

/**
 * Paso 4 — el bloque de la API de Interactividad (momento del cliente).
 */
add_action( 'init', 'recetaia_registrar_bloque' );

function recetaia_registrar_bloque(): void {
	register_block_type( RECETAIA_DIR . '/compilado/tarjeta-receta' );
}
