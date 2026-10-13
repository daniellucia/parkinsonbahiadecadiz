# Párkinson Bahía de Cádiz — web

Rediseño de la web de la **Asociación de Familiares y Enfermos de Párkinson Bahía de Cádiz** (San Fernando, Cádiz): WordPress + Elementor, con un tema y un plugin a medida.

Autor: [Daniel Lucia](https://daniellucia.es)

## Qué hay en este repositorio

Solo se versiona el trabajo propio (ver `.gitignore`); el núcleo de WordPress, Elementor y la carpeta de subidas quedan fuera.

| Ruta | Qué es |
|---|---|
| `wp-content/themes/parkinson-bahia/` | Tema: estilos base, cabecera, pie, plantillas de noticias y páginas |
| `wp-content/plugins/parkinson-bahia-core/` | Plugin: tipos de contenido propios y widgets de Elementor |
| `docs/investigacion.md` | Investigación: datos de la asociación, auditoría de la web actual, preguntas pendientes |
| `CLAUDE.md` | Normas del proyecto (accesibilidad, rendimiento, seguridad, convenciones) |
| `.ddev/` | Entorno local |

## Puesta en marcha en local

Requisitos: [DDEV](https://ddev.com) y Docker.

```bash
ddev start
ddev exec -d /var/www/html/wp-content/themes/parkinson-bahia composer install
ddev exec -d /var/www/html/wp-content/plugins/parkinson-bahia-core composer install
ddev wp plugin activate elementor parkinson-bahia-core
ddev wp theme activate parkinson-bahia
ddev wp pbc seed-home        # crea la portada, las páginas base, las terapias y tres noticias
ddev wp pbc seed-asociacion  # crea la página «La asociación» y sus contenidos
ddev wp pbc seed-centro      # crea la página «Centro de día» y sus contenidos
ddev launch
```

Los tres comandos se pueden repetir sin duplicar nada. Con `--force` reescriben su página; `seed-home` con `--skip-images` no descarga las fotos de la web actual.

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

### Tema `parkinson-bahia` (versión 1.1.1)

- Diseño tomado del proyecto de claude.ai/design «Rediseño Asociación Párkinson Cádiz»: colores, tipografías (Atkinson Hyperlegible Next y Young Serif, alojadas en el tema) y componentes.
- Barra superior con teléfono, horario, tres tamaños de texto y alto contraste (se recuerdan en el navegador).
- Cabecera con logotipo, menú principal y botón destacado; en móvil, botones «Llamar» y «Menú».
- Bloque de contacto común antes del pie y pie con menús, redes sociales y datos legales.
- Plantillas: Actualidad (noticia destacada, filtro por categoría, paginación), noticia, página, página de Elementor a ancho completo, tipos de contenido propios, búsqueda y 404.
- Datos de contacto, botón de cabecera, redes y textos del pie editables en Apariencia → Personalizar.
- Buscador en el pie de todas las páginas.
- Indicador de carga en el botón al enviar cualquier formulario.

### Plugin `parkinson-bahia-core` (versión 1.2.0)

- **Tipo de contenido «Terapias»**: una página por terapia, con resumen, foto y orden.
- **Menú «La asociación» del escritorio**, con los datos que alimentan esa página: Historia (hitos por año), Equipo profesional (puestos y número de personas), Junta directiva (nombre y cargo), Documentos de transparencia (con su archivo) y Redes y federaciones (siglas y web).
- **Menú «Centro de día» del escritorio**: Instalaciones (sala y foto) y Preguntas frecuentes (pregunta, respuesta y tema).
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

- Elementor no carga Google Fonts: las fuentes las sirve el tema.
- «Cabecera de página» admite fondo arena y datos clave; «Pasos» admite fondo azul oscuro y tarjetas de apoyo.
- Comandos `wp pbc seed-home`, `wp pbc seed-asociacion` y `wp pbc seed-centro` para montar esas páginas con el contenido del diseño.

### Páginas

- **Inicio**: montada con los siete widgets y configurada como página de inicio. Actualidad es la página de entradas.
- **La asociación**: montada con siete widgets (cabecera, misión, historia, equipo, junta directiva, transparencia y redes). Faltan la foto de cabecera, las fotos del equipo y los archivos de los documentos, que aparecen como «Próximamente».
- **Centro de día**: montada con siete widgets (cabecera con datos clave, aviso de lista de espera, un día en el centro, cómo solicitar plaza, instalaciones, transporte y preguntas frecuentes). Falta la foto del transporte. El horario y el calendario usan el dato confirmado («cerrado en agosto»), no el del boceto («abierto todo el año»).
- Terapias, Familias y cuidadores, Colabora y Contacto: creadas, con contenido pendiente.

## Pendiente

- Resto de páginas y sus widgets; tipos de contenido que faltan (recursos, vídeos, prensa, financiadores).
- Formularios (contacto, socio, voluntariado), donativos, mapa, cookies y declaración de accesibilidad.
- Redirecciones 301 de la web actual.
- Datos por confirmar con la asociación (ver «Preguntas pendientes» en `docs/investigacion.md`) y fotos originales a mayor resolución: las de la web actual miden 800 px de ancho.
