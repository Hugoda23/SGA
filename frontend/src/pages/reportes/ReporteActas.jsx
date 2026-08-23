import { useState, useEffect } from 'react'
import api, { normList } from '../../api/axios'
import { btn, input, table as tbl, badge } from '../../lib/twClasses'
import Modal from '../../components/Modal'

export default function ReporteActas() {
  const [search, setSearch] = useState('')
  const [asignaciones, setAsignaciones] = useState([])
  const [loading, setLoading] = useState(true)
  const [alertMessage, setAlertMessage] = useState(null)

  useEffect(() => {
    const fetchAsignaciones = async () => {
      try {
        const response = await api.get('/v1/asignaciones')
        setAsignaciones(normList(response.data))
      } catch (error) {
        console.error('Error fetching asignaciones', error)
      } finally {
        setLoading(false)
      }
    }
    fetchAsignaciones()
  }, [])

  const handleDownload = async (id_asignacion) => {
    try {
      const response = await api.get(`/v1/reportes/pdf/acta/${id_asignacion}`, { responseType: 'blob' })
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `acta_asignacion_${id_asignacion}.pdf`)
      document.body.appendChild(link)
      link.click()
      link.remove()
    } catch (error) {
      console.error('Error al descargar el PDF', error)
      setAlertMessage('Error al descargar el PDF. Verifica que el backend esté ejecutándose.')
    }
  }

  const toText = (value) => (value ? String(value).toLowerCase() : '')

  const filtered = asignaciones.filter((row) => {
    const q = search.toLowerCase().trim()
    if (!q) return true
    const curso = toText(row.curso?.nombre_curso)
    const grado = toText(row.grado?.nombre)
    const seccion = toText(row.seccion?.nombre)
    const periodo = toText(row.periodo?.nombre)
    const catedratico = toText(`${row.catedratico?.nombre || ''} ${row.catedratico?.apellido || ''}`)
    const codigo = toText(`ASG-${row.id_asignacion}`)
    return [curso, grado, seccion, periodo, catedratico, codigo].some((v) => v.includes(q))
  })

  return (
    <div className="max-w-7xl mx-auto pb-12">

      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-neutral-800 dark:text-neutral-100 mb-2">Reporte de Actas</h1>
        <p className="text-base font-medium text-neutral-500 dark:text-neutral-400">Genera y descarga en formato PDF las actas de notas finales por asignación (curso, grado y sección).</p>
      </div>

      {/* Table Container */}
      <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">

        {/* Filter Bar */}
        <div className="flex flex-col items-center justify-between gap-4 border-b-2 border-neutral-100 p-4 dark:border-neutral-600">
          <div className="relative w-full sm:w-80">
            <span className="absolute left-4 top-1/2 -translate-y-1/2 text-neutral-400">
              <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input
              type="text"
              placeholder="Buscar por curso, grado, sección, periodo o catedrático..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className={`${input.base} pl-12`}
            />
          </div>
        </div>

        {/* Table */}
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className={tbl.head}>
              <tr>
                <th className={tbl.th}>CÓDIGO</th>
                <th className={tbl.th}>CURSO</th>
                <th className={tbl.th}>GRADO / SECCIÓN</th>
                <th className={tbl.th}>PERIODO</th>
                <th className={tbl.th}>CATEDRÁTICO</th>
                <th className={`${tbl.th} text-center`}>INSCRITOS</th>
                <th className={`${tbl.th} text-right`}>ACCIONES</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-100 dark:divide-neutral-700">
              {loading ? (
                <tr>
                  <td colSpan="7" className="px-4 py-12 text-center">
                    <div className="flex flex-col items-center gap-3 text-neutral-500">
                      <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
                      <span className="font-semibold">Cargando asignaciones...</span>
                    </div>
                  </td>
                </tr>
              ) : filtered.length === 0 ? (
                <tr>
                  <td colSpan="7" className="px-4 py-12 text-center text-neutral-500">
                    <div className="flex flex-col items-center justify-center py-8">
                      <svg className="w-12 h-12 text-neutral-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                      <span className="font-semibold text-neutral-600 dark:text-neutral-300">No hay datos disponibles</span>
                    </div>
                    <p className="text-sm text-neutral-400">Crea asignaciones para poder generar actas.</p>
                  </td>
                </tr>
              ) : (
                filtered.map((row) => (
                  <tr key={row.id_asignacion} className={tbl.row}>
                    <td className={`${tbl.td} font-medium`}>{`ASG-${row.id_asignacion}`}</td>
                    <td className={`${tbl.td} font-medium`}>{row.curso?.nombre_curso || '—'}</td>
                    <td className={tbl.td}>
                      {[row.grado?.nombre, row.seccion?.nombre].filter(Boolean).join(' - ') || '—'}
                    </td>
                    <td className={tbl.td}>{row.periodo?.nombre || '—'}</td>
                    <td className={tbl.td}>
                      {row.catedratico ? `${row.catedratico.nombre} ${row.catedratico.apellido}` : '—'}
                    </td>
                    <td className={`${tbl.td} text-center`}>
                      <span className={badge.primary}>{row.inscripciones?.length || 0}</span>
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-right">
                      <button
                        data-twe-ripple-init
                        onClick={() => handleDownload(row.id_asignacion)}
                        className="rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-bold text-primary transition-colors hover:bg-primary hover:text-white dark:bg-primary-100/10"
                        title="Descargar PDF"
                      >
                        PDF Acta
                      </button>
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
          <button type="button" onClick={() => setAlertMessage(null)} className={`${btn.primary} w-full`}>
            Aceptar
          </button>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300 mb-2">{alertMessage}</p>
      </Modal>
    </div>
  )
}
