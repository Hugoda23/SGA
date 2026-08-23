import { useState, useEffect, useCallback } from 'react'
import { useSearchParams } from 'react-router-dom'
import api from '../../api/axios'
import { btn, badge, table as tbl } from '../../lib/twClasses'
import Modal from '../../components/Modal'

const estadoTarea = (t) => {
  const limite = t.fecha_entrega ? new Date(t.fecha_entrega) : null
  const vencida = limite && limite < new Date()
  const completa = t.total_alumnos > 0 && t.total_entregas >= t.total_alumnos
  if (vencida && t.sin_calificar > 0) return { label: 'Vencida', cls: badge.danger }
  if (vencida) return { label: 'Vencida', cls: badge.neutral }
  if (completa) return { label: 'Completa', cls: badge.success }
  return { label: 'Pendiente', cls: badge.warning }
}

export default function EntregasList() {
  const [searchParams] = useSearchParams()
  const [cursos, setCursos] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [expanded, setExpanded] = useState(new Set(searchParams.get('tarea_id') ? [Number(searchParams.get('tarea_id'))] : []))
  const [detalles, setDetalles] = useState({})
  const [califValues, setCalifValues] = useState({})
  const [calificando, setCalificando] = useState(null)
  const [alertMessage, setAlertMessage] = useState(null)

  const fetchCursos = useCallback(() => {
    setLoading(true)
    api.get('/v1/catedratico/tareas-por-curso')
      .then((r) => setCursos(Array.isArray(r.data?.asignaciones) ? r.data.asignaciones : []))
      .catch((err) => {
        console.error(err)
        setError('No se pudieron cargar las tareas.')
      })
      .finally(() => setLoading(false))
  }, [])

  useEffect(() => { fetchCursos() }, [fetchCursos])

  const toggleTarea = useCallback((id_tarea) => {
    setExpanded((prev) => {
      const next = new Set(prev)
      if (next.has(id_tarea)) {
        next.delete(id_tarea)
      } else {
        next.add(id_tarea)
        if (!detalles[id_tarea]) cargarDetalle(id_tarea)
      }
      return next
    })
  }, [detalles])

  const cargarDetalle = async (id_tarea) => {
    setDetalles((prev) => ({ ...prev, [id_tarea]: { data: prev[id_tarea]?.data || null, loading: true } }))
    try {
      const res = await api.get(`/v1/entregas-tarea/por-tarea/${id_tarea}`)
      const vals = {}
      res.data.alumnos.forEach((a) => {
        if (a.entrega && a.entrega.calificacion !== null) vals[a.entrega.id_entrega] = a.entrega.calificacion
      })
      setCalifValues((prev) => ({ ...prev, ...vals }))
      setDetalles((prev) => ({ ...prev, [id_tarea]: { data: res.data, loading: false } }))
    } catch (err) {
      console.error(err)
      setDetalles((prev) => ({ ...prev, [id_tarea]: { data: null, loading: false } }))
      setAlertMessage('Error al cargar las entregas.')
    }
  }

  const handleCalificar = async (id_entrega, id_tarea) => {
    const val = califValues[id_entrega]
    if (val === undefined || val === '') return
    setCalificando(id_entrega)
    try {
      await api.post(`/v1/entregas-tarea/calificar/${id_entrega}`, { calificacion: parseFloat(val) })
      await cargarDetalle(id_tarea)
      fetchCursos()
      setAlertMessage('Calificación guardada')
    } catch {
      setAlertMessage('Error al calificar')
    } finally {
      setCalificando(null)
    }
  }

  const baseUrl = import.meta.env.VITE_API_URL.replace(/\/api$/, '')
  const storageUrl = baseUrl + '/storage/'

  const stats = cursos.reduce(
    (acc, c) => {
      acc.cursos++
      acc.tareas += c.tareas.length
      c.tareas.forEach((t) => {
        acc.entregas += t.total_entregas
        acc.sinCalificar += t.sin_calificar
      })
      return acc
    },
    { cursos: 0, tareas: 0, entregas: 0, sinCalificar: 0 }
  )

  const statCards = [
    { label: 'Cursos', value: stats.cursos, icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', tone: 'text-primary bg-primary-50 dark:bg-primary-900/30' },
    { label: 'Tareas asignadas', value: stats.tareas, icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', tone: 'text-info bg-info-50 dark:bg-info-900/30' },
    { label: 'Entregas recibidas', value: stats.entregas, icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', tone: 'text-success bg-success-50 dark:bg-success-900/30' },
    { label: 'Por calificar', value: stats.sinCalificar, icon: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', tone: 'text-warning bg-warning-50 dark:bg-warning-900/30' },
  ]

  return (
    <div className="mx-auto max-w-7xl pb-12">
      <div className="mb-8">
        <h1 className="mb-2 text-3xl font-bold text-neutral-800 dark:text-neutral-100">Entregas de Tareas</h1>
        <p className="text-base font-medium text-neutral-500 dark:text-neutral-400">
          Revisa por curso las tareas asignadas y califica las entregas de tus alumnos.
        </p>
      </div>

      {!loading && !error && (
        <div className="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
          {statCards.map((s) => (
            <div key={s.label} className="flex items-center gap-4 rounded-xl bg-white p-5 shadow-4 dark:bg-surface-dark">
              <div className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-lg ${s.tone}`}>
                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={s.icon} /></svg>
              </div>
              <div>
                <p className="text-2xl font-extrabold leading-none text-neutral-800 dark:text-neutral-100">{s.value}</p>
                <p className="mt-1 text-xs font-semibold text-neutral-500 dark:text-neutral-400">{s.label}</p>
              </div>
            </div>
          ))}
        </div>
      )}

      {loading && (
        <div className="flex flex-col items-center py-16 text-primary">
          <div className="mb-4 h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
          <span className="font-semibold">Cargando tus cursos...</span>
        </div>
      )}

      {!loading && error && (
        <div className="flex items-center justify-center gap-3 rounded-xl border border-danger-100 bg-danger-50 p-10 text-danger shadow-sm">
          <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          <span className="font-medium">{error}</span>
        </div>
      )}

      {!loading && !error && cursos.length === 0 && (
        <div className="flex flex-col items-center justify-center gap-4 rounded-xl border border-neutral-200 bg-white py-16 font-medium text-neutral-500 dark:border-neutral-700 dark:bg-surface-dark">
          <svg className="mb-4 h-12 w-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
          No tienes cursos asignados.
        </div>
      )}

      {!loading && !error && cursos.map((curso) => {
        const cursoEntregas = curso.tareas.reduce((acc, t) => acc + t.total_entregas, 0)
        const cursoSinCalificar = curso.tareas.reduce((acc, t) => acc + t.sin_calificar, 0)
        return (
          <section key={curso.id_asignacion} className="mb-8 overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
            <div className="border-b border-primary-100/50 bg-primary-50 p-6 dark:border-primary-900/50 dark:bg-primary-900/20">
              <div className="flex flex-wrap items-center justify-between gap-4">
                <div className="flex items-center gap-4">
                  <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-sm font-extrabold uppercase text-white shadow-primary-3">
                    {curso.nombre_curso.split(' ').slice(0, 2).map((w) => w[0]).join('')}
                  </div>
                  <div>
                    <div className="mb-1 flex items-center gap-2">
                      <span className={badge.primary}>{curso.codigo_curso}</span>
                      {curso.periodo_estado === 'activo' && <span className={badge.success}>Periodo activo</span>}
                    </div>
                    <h2 className="text-xl font-bold leading-tight text-neutral-800 dark:text-neutral-100">{curso.nombre_curso}</h2>
                    <p className="mt-0.5 text-sm font-medium text-neutral-500 dark:text-neutral-400">
                      {curso.grado} - Sección {curso.seccion} · {curso.periodo} · {curso.total_alumnos} alumnos
                    </p>
                  </div>
                </div>
                <div className="flex items-center gap-6">
                  <div className="text-center">
                    <p className="text-2xl font-extrabold text-primary">{curso.tareas.length}</p>
                    <p className="text-xs font-semibold text-neutral-500 dark:text-neutral-400">Tareas</p>
                  </div>
                  <div className="text-center">
                    <p className="text-2xl font-extrabold text-success">{cursoEntregas}</p>
                    <p className="text-xs font-semibold text-neutral-500 dark:text-neutral-400">Entregas</p>
                  </div>
                  <div className="text-center">
                    <p className={`text-2xl font-extrabold ${cursoSinCalificar > 0 ? 'text-warning' : 'text-neutral-400'}`}>{cursoSinCalificar}</p>
                    <p className="text-xs font-semibold text-neutral-500 dark:text-neutral-400">Por calificar</p>
                  </div>
                </div>
              </div>
            </div>

            {curso.tareas.length === 0 ? (
              <div className="flex flex-col items-center justify-center gap-3 py-14 text-neutral-400">
                <svg className="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                <p className="text-sm font-semibold">Este curso aún no tiene tareas asignadas.</p>
              </div>
            ) : (
              <div className="divide-y divide-neutral-100 dark:divide-neutral-700">
                {curso.tareas.map((t) => {
                  const est = estadoTarea(t)
                  const isOpen = expanded.has(t.id_tarea)
                  const detalle = detalles[t.id_tarea]
                  const pct = t.total_alumnos > 0 ? Math.round((t.total_entregas * 100) / t.total_alumnos) : 0
                  return (
                    <div key={t.id_tarea}>
                      <button
                        type="button"
                        onClick={() => toggleTarea(t.id_tarea)}
                        className="flex w-full items-center gap-4 px-6 py-4 text-left transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-700/50"
                      >
                        <span className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition-transform ${isOpen ? 'rotate-90 bg-primary text-white' : 'bg-primary-100 text-primary dark:bg-primary-900/30 dark:text-primary-300'}`}>
                          <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" /></svg>
                        </span>
                        <div className="min-w-0 flex-1">
                          <div className="flex flex-wrap items-center gap-2">
                            <h3 className="truncate font-bold text-neutral-800 dark:text-neutral-100">{t.titulo}</h3>
                            <span className={est.cls}>{est.label}</span>
                          </div>
                          <div className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-neutral-500 dark:text-neutral-400">
                            {t.descripcion && <p className="max-w-md truncate">{t.descripcion}</p>}
                            {t.puntos !== null && t.puntos !== undefined && (
                              <span className="font-bold text-primary">{t.puntos} pts</span>
                            )}
                            {t.fecha_entrega && (
                              <span className="flex items-center gap-1">
                                <svg className="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                {new Date(t.fecha_entrega).toLocaleString('es-GT')}
                              </span>
                            )}
                          </div>
                        </div>
                        <div className="w-40 shrink-0">
                          <div className="mb-1 flex items-center justify-between text-xs font-bold text-neutral-600 dark:text-neutral-300">
                            <span>{t.total_entregas}/{t.total_alumnos} entregas</span>
                            <span>{pct}%</span>
                          </div>
                          <div className="h-2 w-full overflow-hidden rounded-full bg-neutral-100 dark:bg-neutral-700">
                            <div className={`h-full rounded-full transition-all ${t.sin_calificar > 0 ? 'bg-warning' : 'bg-success'}`} style={{ width: `${pct}%` }}></div>
                          </div>
                        </div>
                      </button>

                      {isOpen && (
                        <div className="border-t border-neutral-100 bg-neutral-50/50 px-6 py-6 dark:border-neutral-700 dark:bg-neutral-800/30">
                          {detalle?.loading ? (
                            <div className="flex flex-col items-center gap-3 py-8 text-primary">
                              <div className="h-6 w-6 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
                              <span className="text-sm font-semibold">Cargando entregas...</span>
                            </div>
                          ) : detalle?.data ? (
                            <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
                              <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                  <thead className={tbl.head}>
                                    <tr>
                                      <th className={tbl.th}>Alumno</th>
                                      <th className={`${tbl.th} text-center`}>Estado</th>
                                      <th className={`${tbl.th} text-center`}>Entrega</th>
                                      <th className={`${tbl.th} text-center`}>Fecha Entrega</th>
                                      <th className={`${tbl.th} text-center`}>Calificación {t.puntos !== null && t.puntos !== undefined ? `(/ ${t.puntos})` : '(/ 100)'}</th>
                                      <th className={`${tbl.th} text-center`}></th>
                                    </tr>
                                  </thead>
                                  <tbody className="divide-y divide-neutral-100 dark:divide-neutral-700">
                                    {detalle.data.alumnos.map((a) => {
                                      const entregaVencida = a.entrega?.fecha_entrega && detalle.data.tarea.fecha_entrega &&
                                        new Date(a.entrega.fecha_entrega) > new Date(detalle.data.tarea.fecha_entrega)
                                      return (
                                        <tr key={a.id_alumno} className={tbl.row}>
                                          <td className={`${tbl.td} font-medium`}>{a.alumno_nombre}</td>
                                          <td className={`${tbl.td} text-center`}>
                                            {a.entrega ? (
                                              <span className={entregaVencida ? badge.warning : badge.success}>
                                                {entregaVencida ? 'Tarde' : 'Entregado'}
                                              </span>
                                            ) : (
                                              <span className={badge.neutral}>Pendiente</span>
                                            )}
                                          </td>
                                          <td className={`${tbl.td} text-center`}>
                                            {a.entrega?.archivo ? (
                                              <a href={`${storageUrl}${a.entrega.archivo}`} target="_blank" rel="noreferrer" className="flex items-center justify-center gap-1 text-xs font-bold text-primary underline hover:text-primary-accent-300">
                                                <svg className="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                                {a.entrega.nombre_original || 'Descargar'}
                                              </a>
                                            ) : a.entrega?.link ? (
                                              <a href={a.entrega.link} target="_blank" rel="noreferrer" className="inline-flex max-w-[220px] items-center justify-center gap-1 truncate text-xs font-bold text-primary underline hover:text-primary-accent-300" title={a.entrega.link}>
                                                <svg className="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 010 5.656l-2.828 2.828a4 4 0 01-5.656-5.656l1.5-1.5m8.485-5.657l1.5-1.5a4 4 0 115.656 5.656l-2.828 2.828a4 4 0 01-5.656-5.656l.5-.5" /></svg>
                                                <span className="truncate">{a.entrega.link}</span>
                                              </a>
                                            ) : <span className="text-xs text-neutral-300">—</span>}
                                          </td>
                                          <td className={`${tbl.td} text-center text-xs`}>
                                            {a.entrega?.fecha_entrega ? new Date(a.entrega.fecha_entrega).toLocaleString('es-GT') : '—'}
                                          </td>
                                          <td className={`${tbl.td} text-center`}>
                                            {a.entrega ? (
                                              <input
                                                type="number"
                                                min="0"
                                                max={t.puntos !== null && t.puntos !== undefined ? t.puntos : 100}
                                                step="0.01"
                                                className="w-16 rounded-lg border border-transparent bg-neutral-100 py-1.5 text-center text-xs font-bold text-neutral-700 outline-none transition-all focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary dark:bg-neutral-700/50 dark:text-neutral-200 dark:focus:bg-neutral-800"
                                                value={califValues[a.entrega?.id_entrega] ?? ''}
                                                onChange={(e) => setCalifValues({ ...califValues, [a.entrega.id_entrega]: e.target.value })}
                                              />
                                            ) : <span className="text-xs text-neutral-300">—</span>}
                                          </td>
                                          <td className={`${tbl.td} text-center`}>
                                            {a.entrega && (
                                              <button
                                                onClick={() => handleCalificar(a.entrega.id_entrega, t.id_tarea)}
                                                disabled={calificando === a.entrega.id_entrega}
                                                className={`${btn.primary} disabled:opacity-50`}
                                              >
                                                {calificando === a.entrega.id_entrega ? '...' : 'Calificar'}
                                              </button>
                                            )}
                                          </td>
                                        </tr>
                                      )
                                    })}
                                  </tbody>
                                </table>
                              </div>
                            </div>
                          ) : null}
                        </div>
                      )}
                    </div>
                  )
                })}
              </div>
            )}
          </section>
        )
      })}

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
