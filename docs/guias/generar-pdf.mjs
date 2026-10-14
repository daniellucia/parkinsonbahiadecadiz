/**
 * Genera los PDF de las guías imprimibles a partir de su HTML, con Chromium
 * (PDF etiquetado y con marcadores, para lectores de pantalla).
 *
 * Uso: node docs/guias/generar-pdf.mjs   (necesita el paquete «playwright»)
 * Salida: wp-content/plugins/parkinson-bahia-core/assets/seed/*.pdf, que
 * «wp pbc seed-diagnostico» sube a la biblioteca de medios.
 */
import { chromium } from 'playwright';
import { fileURLToPath, pathToFileURL } from 'node:url';
import path from 'node:path';

const here = path.dirname( fileURLToPath( import.meta.url ) );
const out = path.resolve( here, '../../wp-content/plugins/parkinson-bahia-core/assets/seed' );
const guides = [ 'guia-de-bienvenida', 'preguntas-para-mi-neurologo' ];

const browser = await chromium.launch( process.env.CHROMIUM ? { executablePath: process.env.CHROMIUM } : {} );
const page = await browser.newPage();

for ( const name of guides ) {
	await page.goto( pathToFileURL( path.join( here, name + '.html' ) ).href, { waitUntil: 'networkidle' } );
	await page.evaluate( () => document.fonts.ready );
	await page.pdf( {
		path: path.join( out, name + '.pdf' ),
		format: 'A4',
		printBackground: true,
		preferCSSPageSize: true,
		tagged: true,
		outline: true,
	} );
	console.log( 'PDF:', name );
}

await browser.close();
