import { useState, useEffect, useMemo } from 'react'
import api from '../../api/axios'
import { btn, input, table as tbl, badge } from '../../lib/twClasses'
import Modal from '../../components/Modal'

const NIVELES = ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 'WARNING', 'NOTICE', 'INFO', 'DEBUG']
const POR_PAGINA = 50

const nivelBadge = {
  EMERGENCY: badge.danger,
  ALERT: badge.danger,
  CRITICAL: badge.danger,
  ERROR: badge.danger,
  WARNING: badge.warning,
  NOTICE: badge.info,
  INFO: badge.info,
  DEBUG: badge.neutral,
}

export default function LogList() {
  const [logs, setLogs] = useState([])
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState('')
  const [nivel, setNivel] = useState('')
  const [pagina, setPagina] = useState(1)
  const [detalle, setDetalle] = useState(null)
  const [confirmClear, setConfirmClear] = useState(false)
  const [alertMessage, setAlertMessage] = useState(null)

  const fetchLogs = async () => {
    setLoading(true)
    try {
      const params = {}
      if (nivel) params.nivel = nivel
      const response = await api.get('/v1/logs', { params })
      setLogs(response.data.logs || [])
      setPagina(1)
    } catch {
      setAlertMessage('Error al obtener los logs del sistema.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    fetchLogs()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [nivel])

  const handleClear = async () => {
    try {
      await api.delete('/v1/logs')
      setConfirmClear(false)
      await fetchLogs()
    } catch {
      setAlertMessage('Error al vaciar el archivo de logs.')
    }
  }

  const filtered = useMemo(() => {
    const q = search.toLowerCase().trim()
    if (!q) return logs
    return logs.filter((l) =>
      [l.mensaje, l.excepcion, l.archivo, l.canal, l.fecha].join(' ').toLowerCase().includes(q)
    )
  }, [logs, search])

  const totalPaginas = Math.max(1, Math.ceil(filtered.length / POR_PAGINA))
  const paginados = filtered.slice((pagina - 1) * POR_PAGINA, pagina * POR_PAGINA)

  const irPagina = (p) => {
    if (p < 1 || p > totalPaginas) return
    setPagina(p)
  }

  return (
    <div className="mx-auto max-w-7xl pb-12">
      <div className="mb-8 flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
        <div>
          <h1 className="mb-2 text-3xl font-bold text-neutral-800 dark:text-neutral-100">Logs del Sistema</h1>
          <p className="text-base font-medium text-neutral-500 dark:text-neutral-400">
            Errores y mensajes registrados por el backend en <span className="font-mono text-xs">storage/logs/laravel.log</span>.
          </p>
        </div>
        <div className="flex flex-wrap gap-3">
          <button type="button" onClick={fetchLogs} className={btn.outline}>
            <span className="flex items-center gap-2">
              <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
              Refrescar
            </span>
          </button>
          <button type="button" onClick={() => setConfirmClear(true)} className={btn.outlineDanger}>
            <span className="flex items-center gap-2">
              <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
              Vaciar log
            </span>
          </button>
        </div>
      </div>

      <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
        <div className="flex flex-col items-center justify-between gap-4 border-b-2 border-neutral-100 p-4 dark:border-neutral-600 md:flex-row">
          <div className="relative w-full sm:w-80">
            <span className="absolute left-4 top-1/2 -translate-y-1/2 text-neutral-400">
              <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input
              type="text"
              placeholder="Buscar en mensajes, excepciones o archivos..."
              value={search}
              onChange={(e) => { setSearch(e.target.value); setPagina(1) }}
              className={`${input.base} pl-12`}
            />
          </div>
          <select
            value={nivel}
            onChange={(e) => setNivel(e.target.value)}
            className={`${input.base} w-full sm:w-56`}
          >
            <option value="">Todos los niveles</option>
            {NIVELES.map((n) => <option key={n} value={n}>{n}</option>)}
          </select>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className={tbl.head}>
              <tr>
                <th className={tbl.th}>FECHA/HORA</th>
                <th className={tbl.th}>NIVEL</th>
                <th className={`${tbl.th} w-full`}>MENSAJE</th>
                <th className={tbl.th}>ORIGEN</th>
                <th className={`${tbl.th} text-right`}>ACCIONES</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-100 dark:divide-neutral-700">
              {loading ? (
                <tr>
                  <td colSpan="5" className="px-4 py-12 text-center">
                    <div className="flex flex-col items-center gap-3">
                      <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
                      <span className="font-semibold text-neutral-500">Leyendo archivo de logs...</span>
                    </div>
                  </td>
                </tr>
              ) : paginados.length === 0 ? (
                <tr>
                  <td colSpan="5" className="px-4 py-12 text-center">
                    <div className="flex flex-col items-center justify-center py-8">
                      <svg className="mb-4 h-12 w-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                      <span className="text-lg font-semibold text-neutral-600 dark:text-neutral-300">No hay logs que mostrar</span>
                      <p className="text-sm text-neutral-400">El archivo de logs está vacío o no coincide con los filtros.</p>
                    </div>
                  </td>
                </tr>
              ) : (
                paginados.map((log) => (
                  <tr key={log.id} className={tbl.row}>
                    <td className={`${tbl.td} text-neutral-500`}>
                      <span className="font-mono text-xs">{log.fecha}</span>
                    </td>
                    <td className={tbl.td}>
                      <span className={nivelBadge[log.nivel] || badge.neutral}>{log.nivel}</span>
                    </td>
                    <td className={`${tbl.td} max-w-xl truncate`} title={log.mensaje}>
                      <span className="font-medium text-neutral-900 dark:text-neutral-100">{log.mensaje}</span>
                    </td>
                    <td className={`${tbl.td} max-w-[14rem] truncate text-neutral-500`} title={log.archivo}>
                      <span className="font-mono text-xs">{log.archivo || '—'}</span>
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-right">
                      <button
                        type="button"
                        onClick={() => setDetalle(log)}
                        className="rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-bold text-primary transition-colors hover:bg-primary hover:text-white dark:bg-primary-100/10"
                      >
                        Ver detalle
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {!loading && paginados.length > 0 && (
          <div className="flex flex-col items-center justify-between gap-3 border-t border-neutral-100 px-4 py-3 dark:border-neutral-600 md:flex-row">
            <span className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
              {filtered.length} registro{filtered.length !== 1 ? 's' : ''} · Página {pagina} de {totalPaginas}
            </span>
            <div className="flex gap-2">
              <button type="button" onClick={() => irPagina(pagina - 1)} disabled={pagina === 1} className={`${btn.outline} !px-4 disabled:cursor-not-allowed disabled:opacity-50`}>Anterior</button>
              <button type="button" onClick={() => irPagina(pagina + 1)} disabled={pagina === totalPaginas} className={`${btn.outline} !px-4 disabled:cursor-not-allowed disabled:opacity-50`}>Siguiente</button>
            </div>
          </div>
        )}
      </div>

      <Modal
        open={!!detalle}
        onClose={() => setDetalle(null)}
        title={`Detalle del log · ${detalle?.nivel || ''}`}
        size="3xl"
        scrollable
        footer={
          <button type="button" onClick={() => setDetalle(null)} className={`${btn.primary} w-full`}>
            Cerrar
          </button>
        }
      >
        {detalle && (
          <div className="space-y-4">
            <div className="grid gap-3 sm:grid-cols-2">
              <div>
                <span className="text-xs font-bold uppercase tracking-wide text-neutral-400">Fecha</span>
                <p className="font-mono text-sm text-neutral-700 dark:text-neutral-200">{detalle.fecha}</p>
              </div>
              <div>
                <span className="text-xs font-bold uppercase tracking-wide text-neutral-400">Excepción</span>
                <p className="font-mono text-sm text-neutral-700 dark:text-neutral-200">{detalle.excepcion || '—'}</p>
              </div>
            </div>
            <div>
              <span className="text-xs font-bold uppercase tracking-wide text-neutral-400">Mensaje</span>
              <p className="mt-1 break-words rounded-lg bg-neutral-50 p-3 font-mono text-sm text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200">{detalle.mensaje}</p>
            </div>
            <div>
              <span className="text-xs font-bold uppercase tracking-wide text-neutral-400">Stack trace</span>
              <pre className="mt-1 max-h-96 overflow-auto rounded-lg bg-neutral-900 p-3 font-mono text-xs leading-relaxed text-neutral-100">
                {detalle.stacktrace}
              </pre>
            </div>
          </div>
        )}
      </Modal>

      <Modal
        open={confirmClear}
        onClose={() => setConfirmClear(false)}
        title="Vaciar archivo de logs"
        size="sm"
        footer={
          <div className="flex w-full gap-3">
            <button type="button" onClick={() => setConfirmClear(false)} className={`${btn.neutral} w-full`}>
              Cancelar
            </button>
            <button type="button" onClick={handleClear} className={`${btn.danger} w-full`}>
              Vaciar
            </button>
          </div>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">
          Esta acción borrará todo el contenido del archivo de logs. ¿Deseas continuar?
        </p>
      </Modal>

      <Modal
        open={!!alertMessage}
        onClose={() => setAlertMessage(null)}
        title="Sistema"
        size="sm"
        footer={
          <button type="button" onClick={() => setAlertMessage(null)} className={`${btn.primary} w-full`}>
            Aceptar
          </button>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">{alertMessage}</p>
      </Modal>
    </div>
  )
}
