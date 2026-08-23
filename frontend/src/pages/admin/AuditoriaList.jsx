import { useState, useEffect } from 'react'
import api from '../../api/axios'
import { btn, input, table as tbl, badge } from '../../lib/twClasses'
import Modal from '../../components/Modal'

export default function AuditoriaList() {
  const [logs, setLogs] = useState([])
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState('')
  const [alertMessage, setAlertMessage] = useState(null)

  useEffect(() => {
    fetchLogs()
  }, [])

  const fetchLogs = async () => {
    try {
      const response = await api.get('/v1/bitacoras')
      setLogs(response.data.data || response.data)
    } catch (error) {
      console.error('Error fetching bitácora:', error)
    } finally {
      setLoading(false)
    }
  }

  const handleDownloadPDF = async () => {
    try {
      const response = await api.get('/v1/reportes/pdf/bitacora', { responseType: 'blob' })
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', 'bitacora_auditoria.pdf')
      document.body.appendChild(link)
      link.click()
      link.remove()
    } catch (error) {
      console.error('Error al descargar la bitácora', error)
      setAlertMessage('Error al descargar el PDF de auditoría.')
    }
  }

  const getActionColor = (action) => {
    switch (action?.toUpperCase()) {
      case 'CREAR': 
      case 'INSERT': return badge.success
      case 'ACTUALIZAR': 
      case 'UPDATE': return badge.warning
      case 'ELIMINAR': 
      case 'DELETE': return badge.danger
      case 'LOGIN': return badge.info
      default: return badge.neutral
    }
  }

  const filteredLogs = logs.filter(log => 
    (log.accion && log.accion.toLowerCase().includes(search.toLowerCase())) ||
    (log.tabla_afectada && log.tabla_afectada.toLowerCase().includes(search.toLowerCase())) ||
    (log.descripcion && log.descripcion.toLowerCase().includes(search.toLowerCase())) ||
    (log.usuario && log.usuario.username && log.usuario.username.toLowerCase().includes(search.toLowerCase()))
  )

  return (
    <div className="mx-auto max-w-7xl pb-12">
      <div className="mb-8 flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
        <div>
          <h1 className="mb-2 text-3xl font-bold text-neutral-800 dark:text-neutral-100">Bitácora de Auditoría</h1>
          <p className="text-base font-medium text-neutral-500 dark:text-neutral-400">Registro histórico de todas las acciones y movimientos en el sistema.</p>
        </div>
        <div className="flex gap-3">
          <button
            type="button"
            onClick={fetchLogs}
            className={btn.outline}
          >
            <span className="flex items-center gap-2">
              <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
              Refrescar
            </span>
          </button>
          <button
            type="button"
            onClick={handleDownloadPDF}
            className={btn.primary}
          >
            <span className="flex items-center gap-2">
              <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
              Descargar PDF
            </span>
          </button>
        </div>
      </div>

      <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
        <div className="flex flex-col items-center justify-between gap-4 border-b-2 border-neutral-100 p-4 dark:border-neutral-600">
          <div className="relative w-full sm:w-80">
            <span className="absolute left-4 top-1/2 -translate-y-1/2 text-neutral-400">
              <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input
              type="text"
              placeholder="Buscar por usuario, módulo o acción..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className={`${input.base} pl-12`}
            />
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className={tbl.head}>
              <tr>
                <th className={tbl.th}>FECHA/HORA</th>
                <th className={tbl.th}>USUARIO</th>
                <th className={tbl.th}>ACCIÓN</th>
                <th className={tbl.th}>MÓDULO</th>
                <th className={`${tbl.th} w-full`}>DESCRIPCIÓN</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-100 dark:divide-neutral-700">
              {loading ? (
                <tr>
                  <td colSpan="5" className="px-4 py-12 text-center">
                    <div className="flex flex-col items-center gap-3">
                      <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
                      <span className="font-semibold text-neutral-500">Cargando bitácora...</span>
                    </div>
                  </td>
                </tr>
              ) : filteredLogs.length === 0 ? (
                <tr>
                  <td colSpan="5" className="px-4 py-12 text-center">
                    <div className="flex flex-col items-center justify-center py-8">
                      <svg className="mb-4 h-12 w-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                      <span className="text-lg font-semibold text-neutral-600 dark:text-neutral-300">No se encontraron registros</span>
                      <p className="text-sm text-neutral-400">Ajusta los filtros de búsqueda para encontrar lo que buscas.</p>
                    </div>
                  </td>
                </tr>
              ) : (
                filteredLogs.map((log) => (
                  <tr key={log.id_bitacora} className={tbl.row}>
                    <td className={`${tbl.td} text-neutral-500`}>
                      <span className="font-mono text-xs">{new Date(log.fecha_hora).toLocaleString('es-GT')}</span>
                    </td>
                    <td className={tbl.td}>
                      <span className="font-medium text-neutral-900 dark:text-neutral-100">{log.usuario?.username || 'Sistema'}</span>
                    </td>
                    <td className={tbl.td}>
                      <span className={`${getActionColor(log.accion)} uppercase`}>
                        {log.accion}
                      </span>
                    </td>
                    <td className={tbl.td}>
                      <span className="font-medium">{log.tabla_afectada || 'General'}</span>
                    </td>
                    <td className={`${tbl.td} max-w-md truncate`} title={log.descripcion}>
                      {log.descripcion || `Afectado registro ID: ${log.id_registro}`}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      <Modal
        open={!!alertMessage}
        onClose={() => setAlertMessage(null)}
        title="Sistema"
        size="sm"
        footer={
          <button type="button" onClick={() => setAlertMessage(null)} className={btn.primary}>
            Aceptar
          </button>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">{alertMessage}</p>
      </Modal>
    </div>
  )
}
