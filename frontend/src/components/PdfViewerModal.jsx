import { useEffect, useRef } from 'react'
import { btn } from '../lib/twClasses'

/**
 * Visor de PDF a pantalla casi completa. El <iframe> monta el visor nativo del
 * navegador, que ya trae su propia barra con zoom, imprimir y guardar; los
 * botones del pie son un atajo a lo mismo, más visible para el usuario.
 */
export default function PdfViewerModal({ open, onClose, url, nombreArchivo, titulo }) {
  const iframeRef = useRef(null)

  useEffect(() => {
    if (!open) return
    const prev = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    return () => {
      document.body.style.overflow = prev
    }
  }, [open])

  useEffect(() => {
    if (!open) return
    const onKey = (e) => {
      if (e.key === 'Escape') onClose?.()
    }
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [open, onClose])

  if (!open) return null

  const imprimir = () => {
    try {
      iframeRef.current.contentWindow.focus()
      iframeRef.current.contentWindow.print()
    } catch {
      // Algunos navegadores bloquean print() sobre el PDF incrustado; abrirlo
      // en otra pestaña deja imprimir desde el visor nativo.
      window.open(url, '_blank', 'noopener')
    }
  }

  const descargar = () => {
    const link = document.createElement('a')
    link.href = url
    link.download = nombreArchivo
    document.body.appendChild(link)
    link.click()
    link.remove()
  }

  return (
    <div
      className="fixed inset-0 z-[1055] outline-none"
      tabIndex={-1}
      role="dialog"
      aria-modal="true"
      aria-label={titulo}
    >
      <div className="relative flex h-full w-full items-center justify-center p-2 sm:p-4">
        <div className="fixed inset-0 bg-black/50" onClick={onClose} />
        <div className="relative flex h-full max-h-[95vh] w-full max-w-6xl flex-col overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">

          <div className="flex flex-shrink-0 items-center justify-between gap-4 border-b-2 border-neutral-100 p-4 dark:border-neutral-600">
            <h5 className="truncate text-xl font-medium leading-normal text-neutral-800 dark:text-neutral-200">
              {titulo}
            </h5>
            <button
              type="button"
              onClick={onClose}
              aria-label="Cerrar"
              className="box-content rounded-none border-none text-neutral-500 hover:text-neutral-700 hover:no-underline hover:opacity-75 focus:opacity-100 focus:shadow-none focus:outline-none dark:text-neutral-300 dark:hover:text-neutral-100"
            >
              <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div className="flex-1 overflow-hidden bg-neutral-200 dark:bg-neutral-900">
            <iframe ref={iframeRef} src={url} title={titulo} className="h-full w-full border-0" />
          </div>

          <div className="flex flex-shrink-0 flex-wrap items-center justify-end gap-2 border-t-2 border-neutral-100 p-4 dark:border-neutral-600">
            <button type="button" onClick={onClose} className={btn.neutral}>
              Cerrar
            </button>
            <button type="button" onClick={descargar} className={btn.outline}>
              <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              Guardar
            </button>
            <button type="button" onClick={imprimir} className={btn.primary}>
              <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
              Imprimir
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
