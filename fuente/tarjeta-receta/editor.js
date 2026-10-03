/**
 * Punto de entrada del editor. Tres registros, en el orden de la charla:
 *
 * 1. El panel lateral "Extraer con IA" (IA → post meta).
 * 2. La mitad cliente de la fuente de vinculaciones — getValues() alimenta a los
 *    bloques vinculados con los valores vivos del editor, y getFieldsList() pone
 *    nuestros campos en el desplegable de vinculaciones de WP 6.9.
 * 3. El bloque interactivo en sí (vista previa en vivo desde el meta editado).
 */
import apiFetch from '@wordpress/api-fetch';
import {
	registerBlockType,
	registerBlockBindingsSource,
} from '@wordpress/blocks';
import { Button, Spinner } from '@wordpress/components';
import { store as coreDataStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	PluginDocumentSettingPanel,
	store as editorStore,
} from '@wordpress/editor';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { registerPlugin } from '@wordpress/plugins';
import { useBlockProps } from '@wordpress/block-editor';

import metadatos from './block.json';
import './estilo.scss';

/**
 * Campo del vínculo => etiqueta. Las claves reflejan recetaia_campos_vinculables()
 * en PHP; las claves meta son `recetaia_<campo>`.
 */
/**
 * Cuánto mostrar el cargador cuando la respuesta es de relleno. La paella
 * predefinida vuelve al instante, así que sin esta pausa el botón parpadearía
 * y el resultado no se leería como una extracción.
 */
const DURACION_SIMULACION_MS = 4000;

const esperar = ( ms ) =>
	new Promise( ( resolver ) => setTimeout( resolver, ms ) );

const CAMPOS_VINCULABLES = {
	resumen: __( 'Resumen de la IA', 'tarjeta-receta-ia' ),
	tiempo_preparacion: __( 'Tiempo de preparación', 'tarjeta-receta-ia' ),
	tiempo_coccion: __( 'Tiempo de cocción', 'tarjeta-receta-ia' ),
	raciones: __( 'Raciones', 'tarjeta-receta-ia' ),
	dificultad: __( 'Dificultad', 'tarjeta-receta-ia' ),
};

/* ------------------------------------------------------------------------
 * 1. Barra lateral de extracción con IA — el único sitio donde se ejecuta la
 *    IA. Escribe el meta con editPost(), así que los datos viajan por el flujo
 *    normal de guardado y deshacer, y cada bloque vinculado se actualiza en el
 *    instante en que llega la respuesta.
 * ---------------------------------------------------------------------- */

function PanelExtraccion() {
	const [ extrayendo, setExtrayendo ] = useState( false );

	const { tipoContenido, contenido, meta } = useSelect(
		( select ) => ( {
			tipoContenido: select( editorStore ).getCurrentPostType(),
			contenido: select( editorStore ).getEditedPostContent(),
			meta: select( editorStore ).getEditedPostAttribute( 'meta' ),
		} ),
		[]
	);
	const { editPost } = useDispatch( editorStore );
	const { createSuccessNotice, createErrorNotice, createWarningNotice } =
		useDispatch( noticesStore );

	if ( tipoContenido !== 'post' ) {
		return null;
	}

	const extraer = async () => {
		setExtrayendo( true );

		try {
			const receta = await apiFetch( {
				path: '/recetaia/v1/extraer',
				method: 'POST',
				data: { contenido },
			} );

			/*
			 * Cuando la IA falla, el servidor responde con la receta de relleno
			 * marcada como `simulada` en vez de con un error. Mantenemos el
			 * cargador 4 s antes de pintar los datos, para que el botón se
			 * comporte igual que en una extracción de verdad.
			 */
			if ( receta.simulada ) {
				await esperar( DURACION_SIMULACION_MS );
			}

			editPost( {
				meta: {
					recetaia_resumen: receta.resumen,
					recetaia_tiempo_preparacion: receta.tiempo_preparacion,
					recetaia_tiempo_coccion: receta.tiempo_coccion,
					recetaia_raciones: receta.raciones,
					recetaia_dificultad: receta.dificultad,
					recetaia_ingredientes: receta.ingredientes,
				},
			} );

			if ( receta.simulada ) {
				createWarningNotice(
					sprintf(
						/* translators: %s: motivo por el que falló la IA. */
						__(
							'La IA no ha respondido (%s). Se ha cargado una receta de ejemplo.',
							'tarjeta-receta-ia'
						),
						receta.motivo
					),
					{ type: 'snackbar' }
				);
			} else {
				createSuccessNotice(
					sprintf(
						/* translators: %d: número de ingredientes extraídos. */
						__(
							'Receta extraída — %d ingredientes encontrados. Inserta el patrón “Tarjeta de Receta con IA” para mostrarla.',
							'tarjeta-receta-ia'
						),
						receta.ingredientes.length
					),
					{ type: 'snackbar' }
				);
			}
		} catch ( errorPeticion ) {
			createErrorNotice(
				errorPeticion.message ??
					__( 'La extracción ha fallado.', 'tarjeta-receta-ia' ),
				{ type: 'snackbar' }
			);
		} finally {
			setExtrayendo( false );
		}
	};

	const tieneReceta = !! meta?.recetaia_raciones;

	return (
		<PluginDocumentSettingPanel
			name="recetaia-extraer"
			title={ __( 'Receta con IA', 'tarjeta-receta-ia' ) }
		>
			<p>
				{ __(
					'Extrae datos estructurados de receta del contenido de esta entrada.',
					'tarjeta-receta-ia'
				) }
			</p>
			<Button
				variant="primary"
				onClick={ extraer }
				disabled={ extrayendo }
			>
				{ extrayendo ? (
					<Spinner />
				) : (
					__( 'Extraer receta con IA', 'tarjeta-receta-ia' )
				) }
			</Button>
			{ tieneReceta && (
				<p>
					{ sprintf(
						/* translators: 1: tiempo de preparación, 2: tiempo de cocción, 3: raciones, 4: dificultad. */
						__(
							'Extraído: preparación %1$s · cocción %2$s · %3$d raciones · %4$s',
							'tarjeta-receta-ia'
						),
						meta.recetaia_tiempo_preparacion,
						meta.recetaia_tiempo_coccion,
						meta.recetaia_raciones,
						meta.recetaia_dificultad
					) }
				</p>
			) }
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'tarjeta-receta-ia', { render: PanelExtraccion } );

/* ------------------------------------------------------------------------
 * 2. Fuente de vinculaciones del cliente. La mitad servidor (vinculaciones.php)
 *    es la dueña del renderizado en el frontend; esta mitad alimenta al editor.
 *    getFieldsList() es lo que hace que la fuente aparezca en el desplegable de
 *    vinculaciones (WP 6.9+).
 * ---------------------------------------------------------------------- */

registerBlockBindingsSource( {
	name: 'recetaia/campos',
	usesContext: [ 'postId', 'postType' ],
	getValues( { select, context, bindings } ) {
		const registro = select( coreDataStore ).getEditedEntityRecord(
			'postType',
			context?.postType,
			context?.postId
		);
		const meta = registro?.meta ?? {};
		const valores = {};

		for ( const [ nombreAtributo, vinculo ] of Object.entries(
			bindings
		) ) {
			const campo = vinculo.args?.campo;
			const valor = meta[ `recetaia_${ campo }` ];

			valores[ nombreAtributo ] =
				valor && campo in CAMPOS_VINCULABLES
					? String( valor )
					: __(
							'Esperando el análisis de la IA…',
							'tarjeta-receta-ia'
					  );
		}

		return valores;
	},
	getFieldsList() {
		return Object.entries( CAMPOS_VINCULABLES ).map(
			( [ campo, etiqueta ] ) => ( {
				label: etiqueta,
				type: 'string',
				args: { campo },
			} )
		);
	},
} );

/* ------------------------------------------------------------------------
 * 3. El bloque interactivo. La vista previa del editor lee el meta *editado*,
 *    así que se actualiza en cuanto termina la extracción — sin ida y vuelta de
 *    guardado. Aquí el contador es inerte; las directivas solo se ejecutan en el
 *    frontend.
 * ---------------------------------------------------------------------- */

registerBlockType( metadatos.name, {
	edit: function Edit() {
		const propsBloque = useBlockProps();
		const meta = useSelect(
			( select ) =>
				select( editorStore ).getEditedPostAttribute( 'meta' ),
			[]
		);
		const ingredientes = meta?.recetaia_ingredientes ?? [];

		if ( ! ingredientes.length ) {
			return (
				<div { ...propsBloque }>
					<p className="recetaia-vacio">
						{ __(
							'Esperando el análisis de la IA — extrae la receta desde el panel Receta con IA.',
							'tarjeta-receta-ia'
						) }
					</p>
				</div>
			);
		}

		return (
			<div { ...propsBloque }>
				<div className="recetaia-raciones">
					<span className="recetaia-raciones__etiqueta">
						{ __( 'Raciones', 'tarjeta-receta-ia' ) }
					</span>
					<button className="recetaia-raciones__boton" disabled>
						−
					</button>
					<span className="recetaia-raciones__contador">
						{ meta.recetaia_raciones }
					</span>
					<button className="recetaia-raciones__boton" disabled>
						+
					</button>
				</div>
				<ul className="recetaia-ingredientes">
					{ ingredientes.map( ( elemento, indice ) => (
						<li
							key={ indice }
							className="recetaia-ingredientes__elemento"
						>
							<span className="recetaia-ingredientes__cantidad">
								{ elemento.cantidad }
							</span>
							<span className="recetaia-ingredientes__unidad">
								{ elemento.unidad }
							</span>
							<span className="recetaia-ingredientes__nombre">
								{ elemento.nombre }
							</span>
						</li>
					) ) }
				</ul>
			</div>
		);
	},
} );
