# Ofertas y licitaciones

PHP 8.4, MySQL 8.4 y Vue 3. MVC sin framework backend; Eloquent se usa únicamente como ORM. La interfaz usa Axios y Bootstrap 5.

## Ejecutar

Necesitas Docker con Compose. Desde esta carpeta:

```sh
docker compose -f compose.setup.yaml run --rm configurar
docker compose up --build -d
```

Abre http://localhost:8091. La primera ejecución carga las 49.022 actividades del catálogo UNSPSC entregado en el enunciado; puede tardar un poco. El SQL está en `bd/script.sql` y se ejecuta solo al crear la base de datos.

La configuración genera claves locales en `.env`, sin sobrescribir uno existente. MySQL no publica ningún puerto. Para detener sin perder datos: `docker compose down`. Los datos y adjuntos permanecen en volúmenes de Docker.

Se aceptan `localhost:8091` y `127.0.0.1:8091`, además de `127.0.0.1:8080` para la comprobación interna. Si cambias el dominio o puerto, define `ALLOWED_HOSTS` en `.env` con los nombres y puertos exactos separados por comas; conserva `127.0.0.1:8080`. No incluye direcciones IPv6.

## Uso

Crea una oferta, búscala en el listado y abre su detalle o edición. Al editar, la oferta debe tener al menos un documento. Se admiten PDF y ZIP de hasta 10 MB, con un máximo de cinco archivos nuevos por guardado. Los ZIP se almacenan sin extraerlos.

El Excel incluye todos los resultados de los filtros aplicados, no solo la página visible. Se genera en PHP como XLSX y los textos se escriben como texto, no como fórmulas.

El consecutivo usa el formato `PO-0001-26`: prefijo PO, ID global con al menos cuatro dígitos y año de creación. El estado inicial es `Registrada`; no se añadieron transiciones que el ejercicio no define. Las horas corresponden a Colombia.

`bd/script.sql` crea las tres tablas relacionadas y carga el catálogo UNSPSC. Docker lo ejecuta automáticamente al crear la base de datos.

## Pruebas

```sh
docker compose -f compose.tests.yaml run --build --rm tests
docker compose -f compose.tests.yaml down
```

Catálogo: [maestro del enunciado](https://a.storyblok.com/f/167454/x/8db69f44cd/unspcs-clasificador-de-bienes-y-servicios-de-naciones-unidas-en-espanol.xlsx), hoja Clasificador. Se conservaron sus 49.022 productos y sus códigos, sin recortar nombres.
