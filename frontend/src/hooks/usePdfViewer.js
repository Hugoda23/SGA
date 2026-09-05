import { useCallback, useEffect, useRef, useState } from 'react'
import api from '../api/axios'

/**
 * Descarga un reporte PDF y lo deja listo para mostrarse en <PdfViewerModal>.
 * El blob queda como object URL y se libera al cerrar el visor o al desmontar,
 * para no acumular memoria cuando el usuario abre varios reportes seguidos.
 *
 * abrirPdf() lanza un Error con el mensaje real del backend: como la petición
 * va con responseType 'blob', el JSON de error también llega como Blob y hay
 * que leerlo aparte en vez de tomarlo de err.response.data.message.
 */
export default function usePdfViewer() {
  const [pdf, setPdf] = useState(null)
  const [cargando, setCargando] = useState(null)
  const urlRef = useRef(null)

  const liberar = useCallback(() => {
    if (urlRef.current) {
      URL.revokeObjectURL(urlRef.current)
      urlRef.current = null
    }
  }, [])

  useEffect(() => liberar, [liberar])

  const cerrarPdf = useCallback(() => {
    liberar()
    setPdf(null)
  }, [liberar])

  /**
   * @param {string} endpoint  ruta del reporte, p.ej. `/v1/reportes/pdf/acta/7`
   * @param {object} opciones  nombreArchivo, titulo, params y clave (identifica
   *                           qué botón está cargando cuando hay varios en la
   *                           misma pantalla)
   */
  const abrirPdf = useCallback(async (endpoint, opciones = {}) => {
    const { nombreArchivo, titulo, params, clave } = opciones
    setCargando(clave ?? endpoint)
    try {
      const res = await api.get(endpoint, { responseType: 'blob', params })
      liberar()
      urlRef.current = URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }))
      setPdf({
        url: urlRef.current,
        nombreArchivo: nombreArchivo || 'reporte.pdf',
        titulo: titulo || 'Vista previa',
      })
    } catch (err) {
      throw new Error(await mensajeDeError(err))
    } finally {
      setCargando(null)
    }
  }, [liberar])

  return { pdf, abrirPdf, cerrarPdf, cargando }
}

async function mensajeDeError(err) {
  const data = err?.response?.data
  if (data instanceof Blob) {
    try {
      const json = JSON.parse(await data.text())
      if (json?.message) return json.message
    } catch {
      /* el cuerpo no era JSON; caemos al mensaje genérico */
    }
    return 'No se pudo generar el PDF.'
  }
  return data?.message || 'No se pudo generar el PDF.'
}
