/**
 * Búsqueda de texto tolerante para los filtros que corren en el navegador.
 *
 * Los listados comparaban con toLowerCase().includes(), que ignora mayúsculas
 * pero no acentos: escribir "jose munoz" no encontraba "José Muñoz". Y como el
 * término se buscaba entero dentro de cada campo por separado, "Juan Pérez"
 * tampoco encontraba al alumno cuyo nombre y apellido están en campos
 * distintos.
 *
 * Igual que App\Support\Busqueda en el backend, aquí se compara sin tildes ni
 * mayúsculas y se parte el término en palabras, para que los buscadores del
 * cliente y los del servidor se comporten igual.
 */

/**
 * Pasa un valor a minúsculas y le quita las tildes.
 *
 * NFD separa cada letra acentuada en letra + marca diacrítica, y el reemplazo
 * borra esas marcas (el rango U+0300 a U+036F). Como la eñe se descompone en
 * "n" + tilde combinante, también convierte ñ en n: "munoz" encuentra "Muñoz".
 */
export function normalizar(valor) {
  return String(valor ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
}

/**
 * ¿Coinciden los campos con el término buscado?
 *
 * Cada palabra del término tiene que aparecer en alguno de los campos, sin
 * importar el orden: "perez juan" y "juan perez" encuentran lo mismo. Un
 * término vacío o solo con espacios no filtra nada.
 *
 * Uso: coincide(search, alumno.nombre, alumno.apellido, alumno.codigo_mineduc)
 */
export function coincide(termino, ...campos) {
  const palabras = normalizar(termino).split(/\s+/).filter(Boolean)

  if (palabras.length === 0) return true

  const texto = campos.map(normalizar).join(' ')

  return palabras.every((palabra) => texto.includes(palabra))
}
