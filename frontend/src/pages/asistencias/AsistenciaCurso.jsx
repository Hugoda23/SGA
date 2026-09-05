import { useState, useEffect, useCallback } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import api from '../../api/axios'
import { btn, input, table as tbl, badge, card } from '../../lib/twClasses'
import Modal from '../../components/Modal'
import PdfViewerModal from '../../components/PdfViewerModal'
import usePdfViewer from '../../hooks/usePdfViewer'

const ESTADOS = ['Presente', 'Ausente', 'Justificado']

const tonoBtn = (estado) => {
  if (estado === 'Presente') return { sel: 'border border-success-200 bg-success-100 text-success', base: 'border border-neutral-200 bg-neutral-50 text-neutral-400 hover:border-success-200' }
  if (estado === 'Ausente') return { sel: 'border border-danger-200 bg-danger-100 text-danger', base: 'border border-neutral-200 bg-neutral-50 text-neutral-400 hover:border-danger-200' }
  return { sel: 'border border-warning-200 bg-warning-100 text-warning', base: 'border border-neutral-200 bg-neutral-50 text-neutral-400 hover:border-warning-200' }
}

export default function AsistenciaCurso() {
  const { id_asignacion } = useParams()
  const navigate = useNavigate()

  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [tab, setTab] = useState('registro')
  const [fecha, setFecha] = useState(new Date().toISOString().split('T')[0])
  const [estados, setEstados] = useState({})
  const [saving, setSaving] = useState(false)
  const { pdf, abrirPdf, cerrarPdf, cargando } = usePdfViewer()
  const [alertMessage, setAlertMessage] = useState(null)

  const fetchData = useCallback(async () => {
    setLoading(true)
    try {
      const res = await api.get(`/v1/asistencias/por-asignacion/${id_asignacion}`)
      setData(res.data)
      inicializarFecha(res.data, new Date().toISOString().split('T')[0])
    } catch {
      setError('No se pudieron cargar los datos de asistencia.')
    } finally {
      setLoading(false)
    }
  }, [id_asignacion])

  useEffect(() => { fetchData() }, [fetchData])

  const inicializarFecha = (raw, f) => {
    const registrosFecha = raw?.asistencias_por_fecha?.find((af) => af.fecha === f)
    const est = {}
    raw?.inscripciones?.forEach((ins) => {
      const prev = registrosFecha?.registros?.find((r) => r.id_inscripcion === ins.id_inscripcion)
      est[ins.id_inscripcion] = prev?.estado || ''
    })
    setEstados(est)
  }

  const cargarFecha = (f) => {
    setFecha(f)
    inicializarFecha(data, f)
    setTab('registro')
  }

  const toggleEstado = (id_inscripcion, estado) => {
    setEstados((prev) => ({
      ...prev,
      [id_inscripcion]: prev[id_inscripcion] === estado ? '' : estado,
    }))
  }

  const handleGuardar = async () => {
    setSaving(true)
    try {
      await api.post('/v1/asistencias/guardar-masivo', {
        id_asignacion: parseInt(id_asignacion),
        fecha,
        asistencias: Object.entries(estados).map(([id_inscripcion, estado]) => ({
          id_inscripcion: parseInt(id_inscripcion),
          estado,
        })),
      })
      setAlertMessage('Asistencia guardada correctamente')
      await fetchData()
    } catch {
      setAlertMessage('Error al guardar asistencia')
    } finally {
      setSaving(false)
    }
  }

  const verPDF = async () => {
    try {
      await abrirPdf(`/v1/reportes/pdf/asistencia/${id_asignacion}`, {
        clave: 'dia',
        params: { fecha },
        nombreArchivo: `asistencia_${id_asignacion}_${fecha}.pdf`,
        titulo: `Asistencia del ${fecha}`,
      })
    } catch (err) {
      setAlertMessage(err.message)
    }
  }

  const verPDFFinal = async () => {
    try {
      await abrirPdf(`/v1/reportes/pdf/asistencia-final/${id_asignacion}`, {
        clave: 'final',
        nombreArchivo: `asistencia_final_${id_asignacion}.pdf`,
        titulo: 'Asistencia final del curso',
      })
    } catch (err) {
      setAlertMessage(err.message)
    }
  }

  const historial = data?.asistencias_por_fecha
    ? [...data.asistencias_por_fecha].sort((a, b) => b.fecha.localeCompare(a.fecha))
    : []

  const listaFinal = (data?.inscripciones || []).map((ins) => {
    const conteo = { Presente: 0, Ausente: 0, Justificado: 0, sesiones: 0 }
    data.asistencias_por_fecha.forEach((af) => {
      const reg = af.registros.find((r) => r.id_inscripcion === ins.id_inscripcion)
      if (reg && reg.estado) {
        conteo.sesiones++
        conteo[reg.estado] = (conteo[reg.estado] || 0) + 1
      }
    })
    const pct = conteo.sesiones > 0 ? Math.round((conteo.Presente * 100) / conteo.sesiones) : null
    let badgeCls = badge.neutral
    let estadoLabel = 'Sin registros'
    if (pct !== null) {
      if (pct >= 80) { badgeCls = badge.success; estadoLabel = 'Aprueba' }
      else if (pct >= 60) { badgeCls = badge.warning; estadoLabel = 'En riesgo' }
      else { badgeCls = badge.danger; estadoLabel = 'Reprueba' }
    }
    return { ...ins, ...conteo, pct, badgeCls, estadoLabel }
  })

  const tabs = [
    { id: 'registro', label: 'Registrar / Editar' },
    { id: 'historial', label: 'Historial' },
    { id: 'lista', label: 'Lista Final' },
  ]

  if (loading) return (
    <div className="mx-auto max-w-7xl pb-12">
      <div className="flex flex-col items-center gap-3 py-16 text-neutral-500">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
        <span className="text-lg font-semibold">Cargando...</span>
      </div>
    </div>
  )
  if (error) return (
    <div className="mx-auto max-w-7xl px-4 pb-12">
      <div className="flex items-center gap-3 rounded-xl border border-danger-100 bg-danger-50 p-6 text-danger">
        <svg className="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span className="font-medium">{error}</span>
      </div>
    </div>
  )
  if (!data) return null

  const { asignacion } = data

  return (
    <div className="mx-auto max-w-7xl pb-12">
      <div className="mb-6">
        <button onClick={() => navigate(-1)} className="flex items-center gap-1 text-sm font-semibold text-primary hover:text-primary-accent-300">
          &larr; Volver
        </button>
      </div>

      <div className={`${card} mb-8 flex flex-col items-start justify-between gap-6 md:flex-row md:items-center`}>
        <div>
          <div className="mb-3 inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-bold text-primary dark:bg-primary-900/30 dark:text-primary-300">
            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            Control de Asistencia
          </div>
          <h1 className="text-3xl font-bold text-neutral-800 dark:text-neutral-100">{asignacion.curso}</h1>
        </div>

        <div className="flex rounded-xl bg-neutral-100 p-1 dark:bg-neutral-700">
          {tabs.map((t) => (
            <button
              key={t.id}
              onClick={() => setTab(t.id)}
              className={`rounded-lg px-5 py-2.5 text-sm font-bold transition-colors ${tab === t.id ? 'bg-white text-primary shadow-sm dark:bg-neutral-800 dark:text-primary-300' : 'text-neutral-500 hover:bg-white/60 hover:text-neutral-900 dark:text-neutral-300 dark:hover:bg-neutral-800'}`}
            >
              {t.label}
            </button>
          ))}
        </div>
      </div>

      {tab === 'registro' && (
        <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
          <div className="flex flex-wrap items-end justify-between gap-4 border-b border-neutral-100 bg-neutral-50 px-6 py-4 dark:border-neutral-600 dark:bg-neutral-700/50">
            <div className="w-full max-w-xs">
              <label className={input.label}>Fecha de la clase</label>
              <input type="date" value={fecha} onChange={(e) => cargarFecha(e.target.value)} className={input.base} />
            </div>
            <div className="flex gap-2">
              <button onClick={verPDF} disabled={cargando === 'dia'} className={`${btn.outline} disabled:opacity-50`}>
                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                {cargando === 'dia' ? '...' : 'PDF'}
              </button>
              <button onClick={handleGuardar} disabled={saving} className={`${btn.primary} disabled:opacity-50`}>
                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                {saving ? 'Guardando...' : 'Guardar Asistencia'}
              </button>
            </div>
          </div>

          {data.inscripciones.length === 0 ? (
            <p className="py-12 text-center font-medium text-neutral-500">No hay alumnos inscritos</p>
          ) : (
            <div className="divide-y divide-neutral-100 dark:divide-neutral-700">
              {data.inscripciones.map((ins) => {
                const tono = tonoBtn(estados[ins.id_inscripcion])
                return (
                  <div key={ins.id_inscripcion} className="flex flex-wrap items-center justify-between gap-3 px-6 py-3.5">
                    <span className="font-medium text-neutral-700 dark:text-neutral-200">{ins.alumno_nombre}</span>
                    <div className="flex gap-2">
                      {ESTADOS.map((estado) => (
                        <button
                          key={estado}
                          onClick={() => toggleEstado(ins.id_inscripcion, estado)}
                          className={`rounded-lg px-4 py-1.5 text-xs font-bold transition-all ${estados[ins.id_inscripcion] === estado ? tono.sel : tono.base}`}
                        >
                          {estado}
                        </button>
                      ))}
                    </div>
                  </div>
                )
              })}
            </div>
          )}
        </div>
      )}

      {tab === 'historial' && (
        <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
          <div className="border-b border-neutral-100 bg-neutral-50 px-6 py-4 dark:border-neutral-600 dark:bg-neutral-700/50">
            <h2 className="font-bold text-neutral-800 dark:text-neutral-100">Asistencias anteriores</h2>
            <p className="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">Selecciona una fecha para ver o editar el registro.</p>
          </div>
          {historial.length === 0 ? (
            <p className="py-12 text-center font-medium text-neutral-500">Aún no hay asistencias registradas.</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead className={tbl.head}>
                  <tr>
                    <th className={tbl.th}>Fecha</th>
                    <th className={`${tbl.th} text-center`}>Presentes</th>
                    <th className={`${tbl.th} text-center`}>Ausentes</th>
                    <th className={`${tbl.th} text-center`}>Justificados</th>
                    <th className={`${tbl.th} text-center`}>Registrados</th>
                    <th className={`${tbl.th} text-center`}></th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-neutral-100 dark:divide-neutral-700">
                  {historial.map((af) => {
                    const presentes = af.registros.filter((r) => r.estado === 'Presente').length
                    const ausentes = af.registros.filter((r) => r.estado === 'Ausente').length
                    const justificados = af.registros.filter((r) => r.estado === 'Justificado').length
                    return (
                      <tr key={af.fecha} className={tbl.row}>
                        <td className={`${tbl.td} font-bold`}>
                          {new Date(af.fecha + 'T00:00:00').toLocaleDateString('es-GT', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
                        </td>
                        <td className={`${tbl.td} text-center`}><span className={badge.success}>{presentes}</span></td>
                        <td className={`${tbl.td} text-center`}><span className={badge.danger}>{ausentes}</span></td>
                        <td className={`${tbl.td} text-center`}><span className={badge.warning}>{justificados}</span></td>
                        <td className={`${tbl.td} text-center`}><span className={badge.neutral}>{af.registros.length}</span></td>
                        <td className={`${tbl.td} text-center`}>
                          <button onClick={() => cargarFecha(af.fecha)} className={`${btn.neutral} !px-4 !py-1.5`}>Ver / Editar</button>
                        </td>
                      </tr>
                    )
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      {tab === 'lista' && (
        <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
          <div className="border-b border-neutral-100 bg-neutral-50 px-6 py-4 dark:border-neutral-600 dark:bg-neutral-700/50">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 className="font-bold text-neutral-800 dark:text-neutral-100">Lista final de asistencia</h2>
                <p className="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">Resumen de asistencia por alumno en el curso.</p>
              </div>
              <button onClick={verPDFFinal} disabled={cargando === 'final'} className={`${btn.outline} disabled:opacity-50`}>
                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                {cargando === 'final' ? 'Generando...' : 'Ver PDF'}
              </button>
            </div>
          </div>
          {data.inscripciones.length === 0 ? (
            <p className="py-12 text-center font-medium text-neutral-500">No hay alumnos inscritos</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead className={tbl.head}>
                  <tr>
                    <th className={tbl.th}>Alumno</th>
                    <th className={`${tbl.th} text-center`}>Sesiones</th>
                    <th className={`${tbl.th} text-center`}>Presentes</th>
                    <th className={`${tbl.th} text-center`}>Ausentes</th>
                    <th className={`${tbl.th} text-center`}>Justificados</th>
                    <th className={`${tbl.th} text-center`}>% Asistencia</th>
                    <th className={`${tbl.th} text-center`}>Estado</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-neutral-100 dark:divide-neutral-700">
                  {listaFinal.map((a) => (
                    <tr key={a.id_inscripcion} className={tbl.row}>
                      <td className={`${tbl.td} font-bold`}>{a.alumno_nombre}</td>
                      <td className={`${tbl.td} text-center`}>{a.sesiones}</td>
                      <td className={`${tbl.td} text-center`}><span className={badge.success}>{a.Presente}</span></td>
                      <td className={`${tbl.td} text-center`}><span className={badge.danger}>{a.Ausente}</span></td>
                      <td className={`${tbl.td} text-center`}><span className={badge.warning}>{a.Justificado}</span></td>
                      <td className={`${tbl.td} text-center font-extrabold ${a.pct !== null ? (a.pct >= 80 ? 'text-success' : a.pct >= 60 ? 'text-warning' : 'text-danger') : 'text-neutral-400'}`}>
                        {a.pct !== null ? `${a.pct}%` : '—'}
                      </td>
                      <td className={`${tbl.td} text-center`}><span className={a.badgeCls}>{a.estadoLabel}</span></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      <PdfViewerModal
        open={!!pdf}
        onClose={cerrarPdf}
        url={pdf?.url}
        nombreArchivo={pdf?.nombreArchivo}
        titulo={pdf?.titulo}
      />

      <Modal
        open={!!alertMessage}
        onClose={() => setAlertMessage(null)}
        title="Sistema"
        size="sm"
        footer={
          <button onClick={() => setAlertMessage(null)} className={`${btn.primary} w-full`}>
            Aceptar
          </button>
        }
      >
        <p className="text-center text-sm text-neutral-600 dark:text-neutral-300">{alertMessage}</p>
      </Modal>
    </div>
  )
}
