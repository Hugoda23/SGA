import { lazy } from 'react'

const CLAVE = 'sga_recarga_por_despliegue'

/**
 * lazy() que se recupera de un despliegue nuevo. Cada build cambia el nombre
 * de los chunks (CatedraticoList-<hash>.js) y borra los anteriores, así que
 * una pestaña abierta desde antes pide archivos que ya no existen y la vista
 * no carga. En ese caso se recarga la página una vez para tomar el
 * index.html nuevo; si vuelve a fallar enseguida, el error es otro y se deja
 * subir.
 */
export function lazyConRecarga(importar) {
  return lazy(() =>
    importar().catch((err) => {
      let reciente
      try {
        reciente = Date.now() - (Number(sessionStorage.getItem(CLAVE)) || 0) < 10000
        if (!reciente) sessionStorage.setItem(CLAVE, String(Date.now()))
      } catch {
        // Sin sessionStorage no hay cómo evitar un bucle de recargas.
        throw err
      }
      if (reciente) throw err
      window.location.reload()
      // Mantiene el Suspense en espera mientras el navegador recarga.
      return new Promise(() => {})
    }),
  )
}
