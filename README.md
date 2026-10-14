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
| `wp-content/plugins/parkinson-cookies/` | Plugin: aviso y configuración de cookies, y lista de la política de cookies |
| `wp-content/plugins/parkinson-rgpd/` | Plugin: protección de datos (registro de tratamientos, plazos de conservación, menos datos expuestos) |
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
ddev exec -d /var/www/html/wp-content/plugins/parkinson-cookies composer install
ddev exec -d /var/www/html/wp-content/plugins/parkinson-rgpd composer install
ddev wp plugin activate elementor parkinson-bahia-core parkinson-llms-txt parkinson-cookies parkinson-rgpd
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
ddev wp pbc seed-cookies     # política de cookies, su enlace en el plugin y el menú legal con «Cookies» y «Configurar cookies»
ddev wp pbc seed-diagnostico # página «Me acaban de diagnosticar», sus PDF y el aviso de la portada
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

### Tema `parkinson-bahia` (versión 1.7.0)

- Diseño tomado del proyecto de claude.ai/design «Rediseño Asociación Párkinson Cádiz»: colores, tipografías (Atkinson Hyperlegible Next y Young Serif, alojadas en el tema) y componentes.
- Barra superior con teléfono, horario, tres tamaños de texto y alto contraste (se recuerdan en el navegador). En móvil solo muestra el teléfono; el horario y las opciones de lectura pasan al menú.
- Cabecera con logotipo, menú principal y botón destacado, fija arriba al bajar (salvo en pantallas muy bajas). En móvil y tableta, una sola fila con logotipo, botón de llamar (solo icono por debajo de 420 px) y botón «Menú» con icono de hamburguesa que se convierte en aspa: abre un panel sobre la página con los enlaces (dos columnas en tableta), «Hazte socio», las opciones de lectura (en móvil) y el teléfono con el horario. Se cierra con Escape, con el mismo botón, al pulsar fuera o al salir con el tabulador. Sin JavaScript el menú se ve como una lista.
- En los formularios, las cajas de dos campos de la misma fila quedan alineadas aunque una etiqueta ocupe dos líneas (por ejemplo «Correo electrónico (opcional)»).
- Ajustes para móvil en los widgets: horario del día más compacto, instalaciones en dos columnas, datos de la cabecera de página uno por fila, títulos principales algo más pequeños, botones de portada y llamadas a la acción a todo el ancho, logotipos de financiadores en rejilla y hueco del mapa más bajo.
- Bloque de contacto común antes del pie y pie como en el diseño: logotipos de los financiadores, datos de la asociación, menús «La asociación» (Quiénes somos, Equipo y junta directiva, Transparencia) e «Información legal» (Declaración de accesibilidad, Aviso legal, Privacidad, Cookies y Configurar cookies) y redes sociales. El hueco del mapa lo rellena el plugin. Cada columna del pie lleva su título. Al final, una línea de protección de datos (responsable, «Cómo tratamos tus datos» y «Ejercer tus derechos») que rellena el plugin de RGPD con el filtro `parkinson_bahia/footer_privacy`; el tema le pasa los datos del responsable desde el Personalizador. Sobre el diseño se mantienen X entre las redes y el crédito del autor.
- Plantillas: Actualidad (noticia destacada, filtro por categoría, paginación y «En los medios»), noticia, página, página de Elementor a ancho completo, tipos de contenido propios, búsqueda y 404.
- Datos de contacto, botón de cabecera, redes y textos del pie editables en Apariencia → Personalizar.
- Indicador de carga en el botón al enviar cualquier formulario.

### Plugin `parkinson-bahia-core` (versión 1.16.0)

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

### Plugin `parkinson-cookies` (versión 1.0.0)

Cumple la normativa de cookies (LSSI art. 22.2, RGPD y la guía de cookies de la AEPD de 2023) con lo mínimo y sin estorbar a quien visita.

- **Aviso** abajo de la pantalla solo si la web usa algo que necesita permiso (hoy: los vídeos de YouTube y el mapa de OpenStreetMap). «Aceptar», «Rechazar» y «Elegir» tienen el mismo tamaño y aspecto; la página se puede seguir usando sin contestar. Es lo primero de la página para el teclado y los lectores de pantalla, y reserva su alto para no tapar el final de la página ni el foco.
- **Ventana «Configurar cookies»** por tipos (necesarias, siempre activas; contenido de otras webs; medición de visitas si algún día se añade), con qué servicio hay en cada uno. Se abre desde «Elegir», desde el enlace «Configurar cookies» del pie (cualquier enlace que termine en `#configurar-cookies`) y desde el botón de la política.
- **Nada opcional se carga sin permiso.** Si alguien pulsa un vídeo o el mapa sin haberlo permitido, una ventana pregunta antes de cargarlo («Permitir y continuar» / «Ahora no»). Al retirar un permiso con contenido ya cargado, la página se recarga para quitarlo.
- La elección se guarda 12 meses en la cookie `pbc_cookies` (versión, tipos permitidos y fecha). Se vuelve a preguntar si se añade o quita un servicio opcional o desde **Ajustes → Cookies** («Volver a preguntar»), donde también se elige la página de la política y se ven los servicios declarados.
- **Política de cookies** sin lista escrita a mano: el atajo `[pbc_cookies_tabla]` la pinta a partir de los servicios que declaran el tema y los plugins con el filtro `parkinson_cookies/services`, así que siempre coincide con lo que se carga. `[pbc_cookies_ajustes]` pone el botón «Configurar cookies».
- Sin JavaScript no hay aviso, pero tampoco se carga nada opcional. La página no cambia según quien la visite, así que se puede guardar en caché.

### Plugin `parkinson-rgpd` (versión 1.0.0)

Ayuda a cumplir el RGPD y la LOPDGDD. Todo está en **Ajustes → Protección de datos**.

- **Responsable del tratamiento**: nombre, CIF, dirección, correo, teléfono y delegado de protección de datos. Lo que se deja vacío se toma del Personalizador del tema.
- **Plazos de conservación con borrado automático**: una vez al día se borran para siempre los formularios cerrados que han superado su plazo (propuesta: contacto 12 meses, voluntariado 12, socios 6; 0 = no se borra solo). Botón «Borrar ahora lo caducado». La política de privacidad muestra los plazos configurados con `[pbc_rgpd_plazos]`, así que siempre dice lo que hace la web.
- **Registro de actividades de tratamiento** (art. 30) de los tres formularios: finalidad, base jurídica, datos, destinatarios, transferencias, plazo y medidas de seguridad.
- **Derechos de las personas**: los envíos aparecen en las herramientas de WordPress (Herramientas → Exportar / Borrar datos personales) por el correo de la persona; el DNI y el IBAN salen enmascarados en esa exportación. En cada envío, «Descargar sus datos» da un JSON completo para quien solo dejó su teléfono.
- **Prueba del consentimiento**: cada envío guarda la fecha y hora en que se aceptó la política, la versión de la política y el aviso que se mostró; se ve en el envío.
- **Privacidad por defecto**: comentarios y pingbacks cerrados en toda la web, la API REST no lista usuarios sin sesión, no hay páginas de autor ni nombres de usuario en el mapa del sitio, oEmbed o los datos estructurados de The SEO Framework, y no se usa Gravatar.
- Guía breve de qué hacer si alguien pide sus datos o si hay una brecha de seguridad (notificación a la AEPD en 72 horas).
- El aviso de cada formulario recoge ya lo que pide la primera capa de información: responsable (nombre legal), finalidad, base jurídica, destinatarios (el banco en el caso de los socios), derechos y reclamación ante la AEPD.

### Páginas

- **Actualidad**: plantilla del tema con las noticias y, en su primera página, «En los medios» con cuatro apariciones en prensa.
- **Inicio**: montada con los siete widgets y configurada como página de inicio. Actualidad es la página de entradas.
- **La asociación**: montada con siete widgets (cabecera, misión, historia, equipo, junta directiva, transparencia y redes). Faltan la foto de cabecera, las fotos del equipo y los archivos de los documentos, que aparecen como «Próximamente».
- **Centro de día**: montada con siete widgets (cabecera con datos clave, aviso de lista de espera, un día en el centro, cómo solicitar plaza, instalaciones, transporte y preguntas frecuentes). Falta la foto del transporte. El horario y el calendario usan el dato confirmado («cerrado en agosto»), no el del boceto («abierto todo el año»).
- **Colabora**: montada con cabecera con accesos, formulario de socios, formas de donar, formulario de voluntariado y llamada a empresas. En escritorio, el título y el texto de «Hazte socio o socia», «Haz un donativo» y «Hazte voluntario» se quedan fijos mientras se baja por el formulario (opción «Texto fijo al bajar» de los widgets Formulario y Formas de donar; solo con dos columnas y pantalla de 640 px de alto o más). Los donativos todavía no se cobran en línea (solo transferencia y teléfono) y las cuotas de 5, 10 y 20 € son las del boceto, sin confirmar.
- **Política de privacidad**: publicada y enlazada desde los formularios y el pie. Redactada a partir de los datos conocidos de la asociación y de cómo trata los datos esta web; incluye el formulario de contacto, el mapa y los vídeos (que solo conectan con OpenStreetMap o YouTube al pulsarlos); tiene cuatro puntos marcados «Pendiente de confirmar» (delegado de protección de datos, plazos de conservación, empresa de alojamiento y empresa de alojamiento). Muestra los plazos de conservación configurados y remite a la política de cookies y **debe revisarla la asociación o su asesoría antes de publicar la web**.
- **Terapias**: montada con cabecera azul con accesos a cada terapia, fichas de las ocho terapias y llamada final. Faltan las fotos de musicoterapia y apoyo a familias (sin foto la ficha ocupa todo el ancho).
- **Familias y cuidadores**: montada con cabecera, primeros pasos tras el diagnóstico, las 13 guías que ya publicaba la web actual (con sus PDF), tres vídeos del canal de YouTube y el apoyo a cuidadores. Faltan las fotos de cabecera y de cuidadores, y revisar si los PDF cumplen accesibilidad (vienen de la web actual y de la FEP).
- **Declaración de accesibilidad** y **Aviso legal**: páginas de texto enlazadas desde el pie. La declaración explica qué hace la web para ser fácil de usar y qué no es accesible todavía (PDF de otras entidades, subtítulos de los vídeos, el mapa); dice «parcialmente conforme» con WCAG 2.2 AA y tiene dos puntos «Pendiente de confirmar» (revisión manual completa y plazo de respuesta). El aviso legal recoge los datos de la LSSI (CIF, domicilio, registros) y aclara que la información no sustituye al médico.
- **Política de cookies**: página de texto creada con `seed-cookies`: qué son, qué cookies usa la web (lista generada por el plugin), cómo aceptar, rechazar o cambiar de opinión, cómo borrarlas en los navegadores, datos fuera de la UE y más información. La política de privacidad remite a ella.
- **Me acaban de diagnosticar** (fuera del menú): se llega desde la primera tarjeta de «¿En qué podemos ayudarte?» y desde un aviso bajo la portada de Inicio («¿Te acaban de diagnosticar párkinson?»). Cabecera con un botón para descargar la guía y un índice «En esta página» con enlaces sencillos a cada parte (opción «Botón principal e índice» de la Cabecera de página), «El párkinson, en pocas palabras», qué hacer las primeras semanas (seis pasos), cómo ayuda la asociación aunque no se necesite el centro de día, preguntas para el neurólogo (lista con casillas y ficha para imprimir), guías del tema «Primeros pasos» y llamada final. Dos PDF propios, etiquetados para lectores de pantalla: «Guía de bienvenida» (7 páginas) y «Preguntas para mi neurólogo» (2 páginas); su fuente está en `docs/guias/` (HTML) y se regeneran con `node docs/guias/generar-pdf.mjs`. El texto médico es general y lo debería revisar el equipo de la asociación.
- **Contacto**: montada con el formulario de contacto (nombre, teléfono, correo opcional, tema y mensaje opcional) junto al título y el teléfono en grande, que en escritorio se quedan fijos mientras se baja por el formulario; debajo, el bloque de contacto común con el mapa.

## Pendiente

- Se ha quitado el buscador del pie a petición del cliente. WCAG 2.2 AA (criterio 2.4.5, «Múltiples vías») pide al menos dos formas de llegar a cada página: el menú principal es una; falta decidir la segunda (por ejemplo, un mapa del sitio enlazado desde el pie o el buscador dentro del panel del menú). Sigue habiendo buscador en la página 404 y en los resultados de búsqueda.

- Logotipo de Hidralia y versiones oficiales de los demás logotipos a buena resolución (el de FANDEP mide 115 px y el de «la Caixa» es el antiguo de Obra Social). Fotos que faltan en Terapias, Familias y La asociación.
- Revisar con la asociación los puntos «Pendiente de confirmar» de la declaración de accesibilidad y hacer la revisión manual completa.
- Donativos en línea: elegir pasarela (Stripe, Redsys, Bizum) e integrarla.
- Decidir con la asociación: cuotas de socio, texto del mandato SEPA, cuánto tiempo se conservan los envíos y los puntos pendientes de la política de privacidad.
- Dar de alta Turnstile en Cloudflare y escribir sus claves en Formularios → Ajustes.
- Cookies: comprobar antes de publicar los nombres y la duración de las cookies de YouTube, OpenStreetMap y Cloudflare (la política los toma de la información de cada proveedor) y que la asesoría revise la política. La prueba del consentimiento de cookies es la propia cookie del navegador; no se guarda un registro en el servidor (decidir con la asociación si lo quiere).
- Protección de datos: que la asociación confirme los plazos de conservación (los configurados son una propuesta), si tiene delegado de protección de datos, la empresa de alojamiento (encargada del tratamiento: firmar el contrato del art. 28) y que su asesoría revise la política de privacidad. El registro de tratamientos solo cubre la web; los tratamientos del centro (usuarios, historias, personal) van en el registro propio de la asociación. Los envíos sin cerrar no se borran solos: hay que revisarlos de vez en cuando.
- Redirecciones 301 de la web actual.
- Datos por confirmar con la asociación (ver «Preguntas pendientes» en `docs/investigacion.md`) y fotos originales a mayor resolución: las de la web actual miden 800 px de ancho.
