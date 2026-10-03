/**
 * Paso 4 — toda la "app" del frontend.
 *
 * Sin selectores, sin cableado de eventos, sin actualizaciones manuales del DOM.
 * Las directivas del HTML del servidor declaran QUÉ reacciona; este almacén
 * declara CÓMO. `cantidadEscalada` es estado derivado: el contexto de cada <li>
 * de ingrediente (cantidad) se fusiona con el contexto de la tarjeta (raciones,
 * racionesBase) mediante la herencia de contexto.
 */
import { store, getContext } from '@wordpress/interactivity';

store( 'tarjeta-receta-ia', {
	state: {
		get cantidadEscalada() {
			const { cantidad, raciones, racionesBase } = getContext();
			const escalada = ( cantidad * raciones ) / racionesBase;

			return Number.isInteger( escalada )
				? String( escalada )
				: escalada.toFixed( 1 );
		},
	},
	actions: {
		aumentarRaciones() {
			getContext().raciones += 1;
		},
		disminuirRaciones() {
			const contexto = getContext();
			contexto.raciones = Math.max( 1, contexto.raciones - 1 );
		},
		alternarIngrediente() {
			const contexto = getContext();
			contexto.marcado = ! contexto.marcado;
		},
	},
} );
