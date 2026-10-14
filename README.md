# Párkinson Bahía de Cádiz — web

Rediseño de la web de la **Asociación de Familiares y Enfermos de Párkinson Bahía de Cádiz** (San Fernando, Cádiz): WordPress + Elementor, con un tema y un plugin a medida.

Autor: [Daniel Lucia](https://daniellucia.es)

## Qué hay en este repositorio

Solo se versiona el trabajo propio (ver `.gitignore`); el núcleo de WordPress, Elementor y la carpeta de subidas quedan fuera.

| Ruta | Qué es |
|---|---|
| `wp-content/themes/parkinson-bahia/` | Tema: estilos base, cabecera, pie, plantillas de noticias y páginas |
| `wp-content/plugins/parkinson-bahia-core/` | Plugin: tipos de contenido propios y widgets de Elementor |
| `wp-content/plugins/parkinson-llms-txt/` | Plugin: genera y sirve `llms.txt` para asistentes de IA |
| `docs/investigacion.md` | Investigación: datos de la asociación, auditoría de la web actual, preguntas pendientes |
| `CLAUDE.md` | Normas del proyecto (accesibilidad, rendimiento, seguridad, convenciones) |
| `.ddev/` | Entorno local |

## Puesta en marcha en local

Requisitos: [DDEV](https://ddev.com) y Docker.

```bash
ddev start
ddev exec -d /var/www/html/wp-content/themes/parkinson-bahia composer install
ddev exec -d /var/www/html/wp-content/plugins/parkinson-bahia-core composer install
ddev exec -d /var/www/html/wp-content/plugins/parkinson-llms-txt composer install
ddev wp plugin activate elementor parkinson-bahia-core parkinson-llms-txt
ddev wp theme activate parkinson-bahia
ddev wp pbc seed-home        # crea la portada, las páginas base, las terapias y tres noticias
ddev wp pbc seed-asociacion  # crea la página «La asociación» y sus contenidos
ddev wp pbc seed-centro      # crea la página «Centro de día» y sus contenidos
ddev wp pbc seed-colabora    # crea la página «Colabora»
ddev wp pbc seed-privacidad  # crea y publica la política de privacidad y el menú legal del pie
ddev wp pbc seed-terapias    # completa las terapias y crea la página «Terapias»
ddev wp pbc seed-familias    # guías (PDF de la web actual), vídeos del canal y la página «Familias y cuidadores»
ddev wp pbc seed-contacto    # crea la página «Contacto» con su formulario
ddev wp pbc seed-actualidad  # apariciones en prensa y financiadores del pie
ddev wp pbc seed-pie         # logotipos de los financiadores, accesibilidad, aviso legal y menús del pie
ddev launch
```

Para que el formulario de socios pida DNI e IBAN hace falta una clave de cifrado en `PBC_FORMS_KEY` (se genera con `php -r "echo base64_encode(random_bytes(32));"`). En local va en `.ddev/config.local.yaml`, que no se versiona (después, `ddev restart`); en producción, como constante en `wp-config.php`, con copia guardada fuera del servidor.

Los comandos se pueden repetir sin duplicar nada. Con `--force` reescriben su página; `seed-home` con `--skip-images` no descarga las fotos de la web actual.

## Cómo está construida

- **Cada sección de una página es un widget de Elementor propio.** El cliente monta y edita las páginas en Elementor sin tocar código.
- **Todo lo que es una lista de datos es un tipo de contenido (CPT)** que se administra desde el escritorio; los widgets solo lo muestran.
- **Cabecera y pie se hacen en el tema** (`header.php`, `footer.php`, menús de WordPress), no con Elementor.
- **Los enlaces de los widgets se eligen en un desplegable de páginas**, con «Enlace personalizado» como última opción.
- **Microinteracciones amables** al pasar el ratón o pulsar (color, sombra, un ligero movimiento), nunca animaciones que empiecen solas; se desactivan si la persona ha pedido reducir el movimiento.
- **Un único título principal (h1) por página** y conformidad con **WCAG 2.2 nivel AA**, comprobada con axe-core en cada plantilla.
- **Los botones de envío de formularios muestran un indicador de carga** dentro del propio botón.
- Las versiones del tema y del plugin suben con cada tarea (versionado semántico).
- Accesibilidad, rendimiento y seguridad son requisitos de cada tarea. El detalle está en `CLAUDE.md`.

## Funcionalidades

### Tema `parkinson-bahia` (versión 1.4.2)

- Diseño tomado del proyecto de claude.ai/design «Rediseño Asociación Párkinson Cádiz»: colores, tipografías (Atkinson Hyperlegible Next y Young Serif, alojadas en el tema) y componentes.
- Barra superior con teléfono, horario, tres tamaños de texto y alto contraste (se recuerdan en el navegador).
- Cabecera con logotipo, menú principal y botón destacado; en móvil, botones «Llamar» y «Menú».
- Bloque de contacto común antes del pie y pie como en el diseño: logotipos de los financiadores, datos de la asociación, menús «La asociación» (Quiénes somos, Equipo y junta directiva, Transparencia) e «Información legal» (Declaración de accesibilidad, Aviso legal, Privacidad y cookies) y redes sociales. El hueco del mapa lo rellena el plugin. Sobre el diseño se mantienen el buscador (segunda forma de encontrar páginas, que pide WCAG), X entre las redes y el crédito del autor.
- Plantillas: Actualidad (noticia destacada, filtro por categoría, paginación y «En los medios»), noticia, página, página de Elementor a ancho completo, tipos de contenido propios, búsqueda y 404.
- Datos de contacto, botón de cabecera, redes y textos del pie editables en Apariencia → Personalizar.
- Buscador en el pie de todas las páginas.
- Indicador de carga en el botón al enviar cualquier formulario.

### Plugin `parkinson-bahia-core` (versión 1.12.0)

- **Tipo de contenido «Terapias»**: una página por terapia, con resumen, foto, orden, «Qué trabajamos» (un punto por línea) y «Quién la hace».
- **Menú «La asociación» del escritorio**, con los datos que alimentan esa página: Historia (hitos por año), Equipo profesional (puestos y número de personas), Junta directiva (nombre y cargo), Documentos de transparencia (con su archivo) y Redes y federaciones (siglas y web).
- **Menú «Centro de día» del escritorio**: Instalaciones (sala y foto) y Preguntas frecuentes (pregunta, respuesta y tema).
- **Financiadores** (en el menú «La asociación»): nombre, logotipo y web; salen en la banda «Con el apoyo de» del pie de todas las páginas (sin logotipo se ve el nombre). `seed-pie` pone los logotipos de la web actual y de Wikimedia Commons, recortados y en WebP, a los que no tienen; a Hidralia le falta.
- **En los medios** (en el menú «Entradas»): medio, fecha, titular y enlace; salen en Actualidad, de la más reciente a la más antigua.
- **Menú «Familias» del escritorio**: Guías y fichas (archivo y tema, con sus temas) y Vídeos de ejercicios (dirección de YouTube y miniatura).
- **Widgets de Elementor** (categoría «Párkinson Bahía de Cádiz»):

| Widget | Para qué sirve | De dónde salen los datos |
|---|---|---|
| Portada | Titular, texto, dos botones y foto principal | Campos del widget |
| Accesos por situación | Tarjetas «¿En qué podemos ayudarte?» | Campos del widget |
| Destacado con fotos | Texto, datos clave, botón y tres fotos | Campos del widget |
| Lista de terapias | Lista enlazada de terapias | Tipo de contenido Terapias |
| Pasos | Secuencia numerada (cómo pedir plaza) | Campos del widget |
| Últimas noticias | Las noticias más recientes | Entradas del blog |
| Llamada a colaborar | Mensaje y enlaces grandes (socio, donativo, voluntariado) | Campos del widget |
| Cabecera de página | Migas de pan, título principal, texto y foto opcional | Campos del widget |
| Cita destacada | La misión u otra frase importante | Campos del widget |
| Historia | Línea de tiempo | La asociación → Historia |
| Equipo profesional | Texto, fotos opcionales y lista de puestos | La asociación → Equipo profesional |
| Junta directiva | Personas y cargos | La asociación → Junta directiva |
| Transparencia | Datos registrales y documentos descargables | La asociación → Documentos de transparencia |
| Redes y federaciones | Entidades de las que forma parte | La asociación → Redes y federaciones |
| Aviso | Mensaje importante con botón (lista de espera) | Campos del widget |
| Horario del día | Texto, foto y momentos del día con su hora | Campos del widget |
| Texto con foto | Foto a un lado y texto al otro | Campos del widget |
| Instalaciones | Galería de salas con su nombre | Centro de día → Instalaciones |
| Preguntas frecuentes | Preguntas desplegables, de un tema o de todos | Centro de día → Preguntas frecuentes |
| Formulario | Texto y formulario (hacerse socio, voluntariado o contacto); fondo arena, migas de pan y teléfono en grande opcionales | Campos del widget; los envíos van a Formularios |
| Formas de donar | Texto y una tarjeta por cada medio de donación | Campos del widget |
| Llamada con botón | Título, texto y uno o dos botones, en franja o en recuadro azul claro | Campos del widget |
| Guías para descargar | Guías y fichas con filtro por tema | Familias → Guías y fichas |
| Vídeos de ejercicios | Vídeos que se cargan desde YouTube solo al pulsarlos | Familias → Vídeos de ejercicios |
| Fichas de terapias | Una sección por terapia: foto, resumen, qué trabajamos y quién la hace | Tipo de contenido Terapias |

- Elementor no carga Google Fonts: las fuentes las sirve el tema.
- **Mapa en el bloque de contacto** de todas las páginas: un botón «Ver el mapa» carga OpenStreetMap solo cuando se pulsa; hasta entonces la web no se conecta con nadie.
- «Preguntas frecuentes» añade datos estructurados (FAQPage de schema.org) para los buscadores, un único bloque por página.
- **Formularios** (hacerse socio, voluntariado y contacto):
  - Se envían sin recargar la página, con indicador de carga en el botón y errores explicados junto a cada campo; también funcionan sin JavaScript.
  - Protección contra robots sin acertijos: campo trampa, marca de tiempo firmada, comprobación de origen, límite de envíos y, opcionalmente, **Cloudflare Turnstile**.
  - Los envíos se guardan en **Formularios** del escritorio, solo para administradores. DNI e IBAN se validan (letra y dígitos de control) y se guardan **cifrados**; nunca viajan por correo.
  - **Seguimiento tipo CRM** en la lista de Formularios: columnas con formulario, teléfono (se puede llamar con un clic), correo y qué pide cada persona; filtros por formulario y por estado; accesos «Nuevo / En seguimiento / Cerrado» con su número; búsqueda por nombre, teléfono, correo, mensaje o notas; cambio de estado en lote; y, al abrir un envío, un cuadro «Seguimiento» con el estado y notas internas. Junto al menú se ve cuántos envíos nuevos hay. Además de guardarse, cada envío manda un aviso (nombre y teléfono) al correo de Formularios → Ajustes.
  - Casilla de aceptación con enlace a la política de privacidad (se abre en otra pestaña) y, debajo, la información básica de protección de datos.
  - **Formularios → Ajustes**: correo que recibe los avisos y claves de Turnstile, escritas una sola vez para todos los formularios.
- «Cabecera de página» admite fondo azul oscuro y accesos automáticos a cada terapia (enlazan con «Fichas de terapias»).
- «Cabecera de página» admite también fondo naranja y botones de acceso; las secciones admiten un ancla para enlazarlas.
- «Cabecera de página» admite fondo arena y datos clave; «Pasos» admite fondo azul oscuro y tarjetas de apoyo.
- Comandos `wp pbc seed-home`, `wp pbc seed-asociacion`, `wp pbc seed-centro` y `wp pbc seed-colabora` para montar esas páginas con el contenido del diseño.

### Plugin `parkinson-llms-txt` (versión 1.0.0)

Genera solo el fichero [`llms.txt`](https://llmstxt.org/), que resume la web para los asistentes de inteligencia artificial, y lo mantiene al día cada vez que cambia un contenido.

- `/llms.txt`: nombre, resumen, información importante y una sección por tipo de contenido (páginas, terapias, noticias) con enlace y descripción de cada uno.
- `/llms-full.txt`: todo el texto de los contenidos incluidos en un solo fichero.
- Copia en texto limpio (Markdown) de cada página, en su dirección terminada en `index.html.md`.
- **Ajustes → llms.txt**: activar o desactivar, nombre, resumen, información importante, qué tipos de contenido salen, título y máximo de cada sección, cuáles son secundarios, secciones escritas a mano y vista previa.
- En cada página o entrada, una caja «llms.txt» para dejarla fuera o escribir su descripción.
- Excluye solo lo que no está publicado, tiene contraseña o está marcado «no indexar» en un plugin de SEO.
- Los ajustes de este sitio (resumen, datos de contacto y redes) están guardados en la base de datos local; hay que repetirlos en producción desde Ajustes → llms.txt.

### Páginas

- **Actualidad**: plantilla del tema con las noticias y, en su primera página, «En los medios» con cuatro apariciones en prensa.
- **Inicio**: montada con los siete widgets y configurada como página de inicio. Actualidad es la página de entradas.
- **La asociación**: montada con siete widgets (cabecera, misión, historia, equipo, junta directiva, transparencia y redes). Faltan la foto de cabecera, las fotos del equipo y los archivos de los documentos, que aparecen como «Próximamente».
- **Centro de día**: montada con siete widgets (cabecera con datos clave, aviso de lista de espera, un día en el centro, cómo solicitar plaza, instalaciones, transporte y preguntas frecuentes). Falta la foto del transporte. El horario y el calendario usan el dato confirmado («cerrado en agosto»), no el del boceto («abierto todo el año»).
- **Colabora**: montada con cabecera con accesos, formulario de socios, formas de donar, formulario de voluntariado y llamada a empresas. En escritorio, el título y el texto de «Hazte socio o socia» y «Haz un donativo» se quedan fijos mientras se baja por el formulario (opción «Texto fijo al bajar» de los widgets Formulario y Formas de donar; solo con dos columnas y pantalla de 640 px de alto o más). Los donativos todavía no se cobran en línea (solo transferencia y teléfono) y las cuotas de 5, 10 y 20 € son las del boceto, sin confirmar.
- **Política de privacidad**: publicada y enlazada desde los formularios y el pie. Redactada a partir de los datos conocidos de la asociación y de cómo trata los datos esta web; incluye el formulario de contacto, el mapa y los vídeos (que solo conectan con OpenStreetMap o YouTube al pulsarlos); tiene cuatro puntos marcados «Pendiente de confirmar» (delegado de protección de datos, plazos de conservación, empresa de alojamiento y política de cookies si se añade medición de visitas) y **debe revisarla la asociación o su asesoría antes de publicar la web**.
- **Terapias**: montada con cabecera azul con accesos a cada terapia, fichas de las ocho terapias y llamada final. Faltan las fotos de musicoterapia y apoyo a familias (sin foto la ficha ocupa todo el ancho).
- **Familias y cuidadores**: montada con cabecera, primeros pasos tras el diagnóstico, las 13 guías que ya publicaba la web actual (con sus PDF), tres vídeos del canal de YouTube y el apoyo a cuidadores. Faltan las fotos de cabecera y de cuidadores, y revisar si los PDF cumplen accesibilidad (vienen de la web actual y de la FEP).
- **Declaración de accesibilidad** y **Aviso legal**: páginas de texto enlazadas desde el pie. La declaración explica qué hace la web para ser fácil de usar y qué no es accesible todavía (PDF de otras entidades, subtítulos de los vídeos, el mapa); dice «parcialmente conforme» con WCAG 2.2 AA y tiene dos puntos «Pendiente de confirmar» (revisión manual completa y plazo de respuesta). El aviso legal recoge los datos de la LSSI (CIF, domicilio, registros) y aclara que la información no sustituye al médico.
- **Contacto**: montada con el formulario de contacto (nombre, teléfono, correo opcional, tema y mensaje opcional) junto al título y el teléfono en grande; debajo, el bloque de contacto común con el mapa.

## Pendiente

- Logotipo de Hidralia y versiones oficiales de los demás logotipos a buena resolución (el de FANDEP mide 115 px y el de «la Caixa» es el antiguo de Obra Social). Fotos que faltan en Terapias, Familias y La asociación.
- Revisar con la asociación los puntos «Pendiente de confirmar» de la declaración de accesibilidad y hacer la revisión manual completa.
- Donativos en línea: elegir pasarela (Stripe, Redsys, Bizum) e integrarla.
- Decidir con la asociación: cuotas de socio, texto del mandato SEPA, cuánto tiempo se conservan los envíos y los puntos pendientes de la política de privacidad.
- Dar de alta Turnstile en Cloudflare y escribir sus claves en Formularios → Ajustes.
- Aviso de cookies (hoy no hace falta: no hay cookies propias ni de terceros hasta que se pulsa el mapa o un vídeo).
- Redirecciones 301 de la web actual.
- Datos por confirmar con la asociación (ver «Preguntas pendientes» en `docs/investigacion.md`) y fotos originales a mayor resolución: las de la web actual miden 800 px de ancho.
