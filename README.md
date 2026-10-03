# Tarjeta de Receta con IA

Plugin de demostración para WordCamp que enseña, en un solo flujo, cómo encajan tres piezas de WordPress moderno:

1. **IA → datos estructurados.** El Cliente de IA de WordPress 7.0 convierte el texto libre de una entrada (el correo de la abuela con la receta) en JSON que cumple un esquema, y ese JSON aterriza en **post meta** registrado.
2. **Datos → maquetación.** Una fuente de **Vínculos de Bloques** (Block Bindings) conecta ese meta a bloques **del núcleo** — párrafos y grupos — sin ningún bloque de presentación a medida.
3. **Maquetación → interacción.** Un único bloque propio usa la **API de Interactividad** para que la tarjeta de ingredientes reaccione en el frontend (escalar raciones, tachar ingredientes) sin escribir una sola línea de manipulación del DOM.

El hilo conductor es el **esquema**: un mismo contrato JSON (`incluidos/esquema.php`) le dice a la IA qué debe devolver, refleja el registro del post meta y define qué campos pueden vincularse. Ese acuerdo es lo que hace la salida de la IA *verificable* en vez de "el HTML que al modelo le apeteciera".

---

## Qué hace, paso a paso

### Paso 1 — El contrato (`incluidos/esquema.php`)

Define `recetaia_esquema_receta()`, un esquema JSON con `resumen`, `tiempo_preparacion`, `tiempo_coccion`, `raciones`, `dificultad` (enum: *Fácil* / *Intermedia* / *Avanzada*) e `ingredientes` (array de objetos `{ cantidad, unidad, nombre }`).

También expone `recetaia_campos_vinculables()`: el mapa `campo del vínculo => clave meta` de los campos escalares. Los ingredientes quedan fuera a propósito — un array estructurado pertenece al bloque interactivo, no a una vinculación de atributo de tipo cadena.

### Paso 2 — Almacenamiento y extracción (`incluidos/meta.php`, `incluidos/ia.php`)

- **`meta.php`** registra seis campos de post meta (`recetaia_resumen`, `recetaia_tiempo_preparacion`, `recetaia_tiempo_coccion`, `recetaia_raciones`, `recetaia_dificultad`, `recetaia_ingredientes`) con `show_in_rest`, saneado y `auth_callback` basado en `edit_posts`. `show_in_rest` es imprescindible: el editor los lee y escribe por REST, y la fuente de vinculaciones del cliente los lee del registro de entidad editado.
- **`ia.php`** contiene `recetaia_extraer_receta()`, la única función que llama a la IA. Usa `wp_ai_client_prompt()` con `as_json_response( recetaia_esquema_receta() )`, de modo que **el esquema es el prompt**, y después valida la respuesta con `rest_validate_value_from_schema()`.

Esa misma extracción se expone por **tres entradas**:

| Entrada | Dónde | Comportamiento ante un fallo |
| --- | --- | --- |
| Botón de la barra lateral del editor | Panel «Receta con IA» | Recibe la paella de relleno marcada como `simulada` |
| Ruta REST `POST /recetaia/v1/extraer` | API REST | Igual: red de seguridad para la demo |
| Habilidad `tarjeta-receta-ia/extraer-receta` | Abilities API + Adaptador MCP | Devuelve el `WP_Error` real — un agente no debe tragarse datos inventados |

La Habilidad se registra en la categoría `recetas` con `input_schema` y `output_schema`, así que cualquier agente conectado por MCP (Claude, por ejemplo) puede descubrirla y llamarla sin integración a medida: los esquemas *son* la documentación.

### Paso 3 — Vínculos de Bloques (`incluidos/vinculaciones.php`, `incluidos/patron.php`)

- **`vinculaciones.php`** registra la fuente `recetaia/campos`. Su `get_value_callback` se ejecuta en PHP durante `render_block`, así que los bloques de párrafo del núcleo se convierten en el motor de plantillas de los datos de la IA. Si no hay valor todavía, devuelve «Esperando el análisis de la IA…».
- **`patron.php`** registra el patrón de bloques **Tarjeta de Receta con IA**: grupos, párrafos y el bloque interactivo. Lo importante es lo que *no* hay — ningún bloque de presentación propio, ninguna retrollamada de renderizado para maquetar. Solo `metadata.bindings` apuntando a `recetaia/campos`.

La mitad cliente de la fuente se registra en `fuente/tarjeta-receta/editor.js` con `registerBlockBindingsSource()`: `getValues()` alimenta a los bloques vinculados con los valores vivos del editor y `getFieldsList()` hace que los campos aparezcan en el desplegable de vinculaciones de WP 6.9+.

### Paso 4 — API de Interactividad (`fuente/tarjeta-receta/`)

El bloque `recetaia/tarjeta-receta` se renderiza en el servidor desde el meta (`renderizado.php`) con el HTML completo — cantidades incluidas — así que la tarjeta se lee antes de la hidratación y sin JavaScript. Después, las directivas toman el relevo:

- `data-wp-interactive`, `data-wp-context` para las raciones y cada ingrediente,
- `data-wp-text="state.cantidadEscalada"` para las cantidades,
- `data-wp-on--click` para los botones `+` / `−` y para tachar ingredientes,
- `data-wp-class--esta-marcado` para el estado visual.

`vista.js` es toda la «app» del frontend: un `store()` con tres acciones y un getter de **estado derivado**, `cantidadEscalada`, que combina el contexto del `<li>` (`cantidad`) con el de la tarjeta (`raciones`, `racionesBase`) gracias a la herencia de contexto. El mismo cálculo existe en PHP vía `wp_interactivity_state()`, de modo que el HTML inicial ya muestra las cantidades correctas.

---

## Estructura del proyecto

```
tarjeta-receta-ia.php          Cabecera del plugin, constantes y carga de los módulos
incluidos/
  esquema.php                  El contrato: esquema JSON + campos vinculables
  meta.php                     Registro del post meta (show_in_rest)
  ia.php                       Extracción con IA + ruta REST + Habilidad (MCP)
  vinculaciones.php            Fuente de Vínculos de Bloques (lado servidor)
  patron.php                   Patrón de bloques del núcleo vinculados
fuente/tarjeta-receta/         Código fuente del bloque interactivo
  block.json, renderizado.php, editor.js, vista.js, estilo.scss
compilado/tarjeta-receta/      Salida de la compilación (lo que registra WordPress)
```

---

## Requisitos

- WordPress **7.0** o superior
- PHP **7.4** o superior
- Para el modo real de IA: el plugin **AI Client** de WordPress (`wp_ai_client_prompt()`) con un proveedor configurado
- Para la Habilidad: la **Abilities API** y, opcionalmente, el **Adaptador MCP**

---

## Instalación y uso

```bash
pnpm install
pnpm run build     # o `pnpm run start` para desarrollo con recarga
```

Activa el plugin y después:

1. Abre una entrada y pega el texto de una receta sin estructurar.
2. En la barra lateral del documento, panel **Receta con IA**, pulsa **Extraer receta con IA**.
3. Inserta el patrón **Tarjeta de Receta con IA**. Los bloques vinculados se rellenan al instante desde el meta editado.
4. Publica y abre el frontend: los botones `+` / `−` reescalan todas las cantidades y al pulsar un ingrediente se tacha.

### Modo simulación

La demo trae un interruptor de seguridad para cuando el wifi del recinto falla. `RECETAIA_USAR_SIMULACION` (definida en `tarjeta-receta-ia.php`, por defecto `false`) hace que la extracción devuelva una paella valenciana predefinida sin llamar a ningún proveedor:

```php
// wp-config.php
define( 'RECETAIA_USAR_SIMULACION', true );
```

Aunque esté en `false`, la ruta REST tiene red: si la IA falla —sin proveedor configurado, error del proveedor, JSON que no cumple el esquema— devuelve la receta de relleno marcada como `simulada` y el editor muestra un aviso en vez de dejar el botón a medias. El editor además mantiene el cargador 4 segundos en ese caso, para que la demo se lea igual que una extracción real.

---

## Scripts disponibles

| Comando | Qué hace |
| --- | --- |
| `pnpm run build` | Compila `fuente/` a `compilado/` (con módulos experimentales) |
| `pnpm run start` | Igual, en modo vigilancia |
| `pnpm run lint:js` | Linter de JavaScript |
| `pnpm run plugin-zip` | Genera el ZIP distribuible del plugin |

---

## Licencia

GPL-2.0-or-later · Autor: Imran Sayed
