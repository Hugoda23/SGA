import { useState, useEffect, useCallback, useRef } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'
import api from '../../api/axios'
import { btn, card, badge } from '../../lib/twClasses'
import Modal from '../../components/Modal'

const FORMATOS_ACEPTADOS = '.pdf,.zip,.rar,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.odt,.txt,.jpg,.jpeg,.png,.gif'

const estadoBadge = {
  planificado: badge.info,
  en_progreso: badge.warning,
  completado: badge.success,
}

const estadoLabel = {
  planificado: 'Planificado',
  en_progreso: 'En progreso',
  completado: 'Completado',
}

export default function CursoAlumno() {
  const { id_asignacion } = useParams()
  const navigate = useNavigate()
  const { user } = useAuth()
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [tab, setTab] = useState('inicio')

  const [modalSubida, setModalSubida] = useState(null)
  const [presentando, setPresentando] = useState(false)
  const [successModal, setSuccessModal] = useState(null)
  const [alertMessage, setAlertMessage] = useState(null)
  const fileInputRefs = useRef({})
  const [modalLinkTarea, setModalLinkTarea] = useState(null)
  const [linkValue, setLinkValue] = useState('')
  const [enviandoLink, setEnviandoLink] = useState(false)

  const isAlumno = user?.roles?.some((r) => r.nombre === 'alumno')

  const cargar = useCallback(() => {
    if (!isAlumno) { setLoading(false); return }
    setLoading(true)
    api.get(`/v1/alumno/curso/${id_asignacion}`)
      .then((r) => setData(r.data))
      .catch((err) => setError(err.response?.data?.error || 'Error al cargar el curso'))
      .finally(() => setLoading(false))
  }, [id_asignacion, isAlumno])

  useEffect(() => { cargar() }, [cargar])

  const descargarMaterial = async (m) => {
    try {
      const res = await api.get(`/v1/archivos/${m.id_archivo}/descargar`, { responseType: 'blob' })
      const url = URL.createObjectURL(new Blob([res.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', m.nombre_archivo || 'descarga')
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      URL.revokeObjectURL(url)
    } catch {
      setAlertMessage('Error al descargar el archivo')
    }
  }

  const handleSubir = async (tarea, file) => {
    if (!file) return
    const formData = new FormData()
    formData.append('archivo', file)
    setModalSubida({ id_tarea: tarea.id_tarea, fase: 'subiendo', progreso: 0, nombre: file.name, id_entrega: null, tipo: 'archivo' })
    try {
      const res = await api.post(`/v1/alumno/curso/${id_asignacion}/entregar/${tarea.id_tarea}`, formData, {
        headers: { 'Content-Type': null },
        onUploadProgress: (e) => {
          if (e.total) setModalSubida((prev) => prev ? { ...prev, progreso: Math.round((e.loaded * 100) / e.total) } : prev)
        },
      })
      setModalSubida((prev) => prev ? { ...prev, fase: 'subido', id_entrega: res.data.id_entrega } : prev)
      if (fileInputRefs.current[tarea.id_tarea]) fileInputRefs.current[tarea.id_tarea].value = ''
      cargar()
    } catch (err) {
      setModalSubida(null)
      setAlertMessage(err.response?.data?.error || err.response?.data?.message || 'Error al entregar la tarea')
    }
  }

  const presentarEntrega = async (id_entrega, nombre, tipo = 'archivo') => {
    if (!id_entrega || presentando) return
    setPresentando(true)
    try {
      await api.post(`/v1/entregas-tarea/presentar/${id_entrega}`)
      setModalSubida(null)
      setSuccessModal({ nombre, tipo })
      cargar()
    } catch (err) {
      setModalSubida(null)
      setAlertMessage(err.response?.data?.error || err.response?.data?.message || 'Error al presentar la tarea')
    } finally {
      setPresentando(false)
    }
  }

  const abrirModalLink = (tarea) => {
    setLinkValue(tarea.mi_entrega?.link || '')
    setModalLinkTarea(tarea.id_tarea)
  }

  const handleEnviarLink = async () => {
    if (!modalLinkTarea) return
    const link = linkValue.trim()
    if (!link) { setAlertMessage('Ingresa el enlace de tu entrega'); return }
    setEnviandoLink(true)
    try {
      const res = await api.post(`/v1/alumno/curso/${id_asignacion}/entregar/${modalLinkTarea}`, { link })
      setModalLinkTarea(null)
      setLinkValue('')
      setModalSubida({ id_tarea: modalLinkTarea, fase: 'subido', progreso: 100, nombre: link, id_entrega: res.data.id_entrega, tipo: 'enlace' })
      cargar()
    } catch (err) {
      setAlertMessage(err.response?.data?.error || err.response?.data?.message || 'Error al enviar el enlace')
    } finally {
      setEnviandoLink(false)
    }
  }

  const tabs = [
    { id: 'inicio', label: 'Avance' },
    { id: 'tareas', label: 'Tareas' },
    { id: 'materiales', label: 'Materiales' },
    { id: 'evaluaciones', label: 'Evaluaciones' },
    { id: 'anuncios', label: 'Anuncios' },
  ]

  if (loading) return (
    <div className="mx-auto max-w-6xl pb-12">
      <div className="flex flex-col items-center gap-3 py-16 text-neutral-500">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
        <span className="text-lg font-semibold">Cargando curso...</span>
      </div>
    </div>
  )
  if (error) return (
    <div className="mx-auto max-w-6xl px-4 pb-12">
      <div className="flex items-center gap-3 rounded-xl border border-danger-100 bg-danger-50 p-6 text-danger">
        <svg className="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span className="font-medium">{error}</span>
      </div>
    </div>
  )
  if (!data) return null

  const { asignacion, unidades, tareas, materiales, evaluaciones, anuncios, horarios, zonas } = data

  return (
    <div className="mx-auto max-w-6xl pb-12">
      <div className="mb-6">
        <button onClick={() => navigate(-1)} className="flex items-center gap-1 text-sm font-semibold text-primary hover:text-primary-accent-300">
          &larr; Volver
        </button>
      </div>

      <div className={`${card} mb-8 flex flex-col items-start justify-between gap-6 md:flex-row md:items-center`}>
        <div className="min-w-0">
          <div className="mb-3 inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-bold text-primary dark:bg-primary-900/30 dark:text-primary-300">
            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
            {asignacion.codigo_curso}
          </div>
          <h1 className="text-3xl font-bold text-neutral-800 dark:text-neutral-100">{asignacion.curso}</h1>
          <p className="mt-1 text-sm font-medium text-neutral-500 dark:text-neutral-400">
            {asignacion.grado} &quot;{asignacion.seccion}&quot; · {asignacion.periodo} · Catedrático: {asignacion.catedratico}
          </p>
        </div>

        <div className="w-full min-w-0 overflow-x-auto md:w-auto">
          <div className="flex w-max gap-1 rounded-xl bg-neutral-100 p-1 dark:bg-neutral-700">
            {tabs.map((t) => (
              <button
                key={t.id}
                onClick={() => setTab(t.id)}
                className={`shrink-0 whitespace-nowrap rounded-lg px-4 py-2.5 text-sm font-bold transition-colors ${tab === t.id ? 'bg-white text-primary shadow-sm dark:bg-neutral-800 dark:text-primary-300' : 'text-neutral-500 hover:bg-white/60 hover:text-neutral-900 dark:text-neutral-300 dark:hover:bg-neutral-800'}`}
              >
                {t.label}
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Resumen del curso */}
      <div className="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div className="rounded-xl bg-white p-4 shadow-4 dark:bg-surface-dark">
          <p className="text-xs font-semibold uppercase tracking-wide text-neutral-400 dark:text-neutral-500">Semanas programadas</p>
          <p className="mt-1 text-lg font-bold text-neutral-800 dark:text-neutral-100">{unidades.length}</p>
        </div>
        <div className="rounded-xl bg-white p-4 shadow-4 dark:bg-surface-dark">
          <p className="text-xs font-semibold uppercase tracking-wide text-neutral-400 dark:text-neutral-500">Tareas</p>
          <p className="mt-1 text-lg font-bold text-neutral-800 dark:text-neutral-100">{tareas.length}</p>
        </div>
        <div className="rounded-xl bg-white p-4 shadow-4 dark:bg-surface-dark">
          <p className="text-xs font-semibold uppercase tracking-wide text-neutral-400 dark:text-neutral-500">Materiales</p>
          <p className="mt-1 text-lg font-bold text-neutral-800 dark:text-neutral-100">{materiales.length}</p>
        </div>
        <div className="rounded-xl bg-white p-4 shadow-4 dark:bg-surface-dark">
          <p className="text-xs font-semibold uppercase tracking-wide text-neutral-400 dark:text-neutral-500">Horarios</p>
          <p className="mt-1 text-lg font-bold text-neutral-800 dark:text-neutral-100">{horarios.length}</p>
          <p className="text-xs text-neutral-400">
            {horarios.slice(0, 2).map((h) => `${h.dia_semana} ${h.hora_inicio}`).join(', ')}
          </p>
        </div>
      </div>

      {tab === 'inicio' && (
        <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
          <div className="border-b border-neutral-100 bg-neutral-50 px-6 py-4 dark:border-neutral-600 dark:bg-neutral-700/50">
            <h2 className="text-xl font-bold text-neutral-800 dark:text-neutral-100">Avance Programático</h2>
          </div>
          <div className="p-8">
            {unidades.length === 0 ? (
              <div className="py-10 text-center text-sm text-neutral-400 dark:text-neutral-500">
                El catedrático aún no ha publicado el avance programático.
              </div>
            ) : (
              <div className="relative ml-4 space-y-10 border-l-2 border-neutral-100 py-2 pl-8 dark:border-neutral-600">
                {unidades.map((u) => (
                  <div key={u.id_unidad} className="relative">
                    <div className="absolute -left-[45px] top-0 flex h-7 w-7 items-center justify-center rounded-full border-2 text-sm font-bold ring-4 ring-white dark:ring-surface-dark">
                      {u.numero_semana || '•'}
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                      <h3 className="text-lg font-bold text-neutral-800 dark:text-neutral-100">{u.titulo}</h3>
                      <span className={estadoBadge[u.estado] || badge.info}>{estadoLabel[u.estado] || 'Planificado'}</span>
                    </div>
                    {(u.fecha_inicio || u.fecha_fin) && (
                      <p className="mt-0.5 text-xs font-medium text-neutral-400 dark:text-neutral-500">
                        {u.fecha_inicio || '¿'} → {u.fecha_fin || '¿'}
                      </p>
                    )}
                    {u.temas && <p className="mt-1 text-sm font-medium text-neutral-500 dark:text-neutral-400">Temas: {u.temas}</p>}
                    {u.competencia && <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Competencia: {u.competencia}</p>}
                    {u.tareas.length > 0 && (
                      <div className="mt-2 flex flex-wrap items-center gap-2">
                        {u.tareas.map((t) => {
                          const estadoEntrega = t.mi_entrega?.estado === 'entregada' ? '· entregada' : t.mi_entrega?.estado === 'borrador' ? '· borrador' : ''
                          return (
                            <button
                              key={t.id_tarea}
                              onClick={() => setTab('tareas')}
                              className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors ${t.mi_entrega?.estado === 'entregada' ? 'bg-success-50 text-success dark:bg-success-900/30 dark:text-success-300' : t.mi_entrega?.estado === 'borrador' ? 'bg-warning-50 text-warning dark:bg-warning-900/30 dark:text-warning-300' : 'bg-neutral-100 text-neutral-500 hover:bg-primary-50 hover:text-primary dark:bg-neutral-700 dark:text-neutral-300'}`}
                            >
                              {t.titulo} {estadoEntrega}
                            </button>
                          )
                        })}
                      </div>
                    )}
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      )}

      {tab === 'tareas' && (
        <div className="space-y-4">
          {tareas.length === 0 ? (
            <div className="rounded-xl bg-white p-12 text-center shadow-4 dark:bg-surface-dark">
              <p className="text-sm text-neutral-400 dark:text-neutral-500">No hay tareas en este curso.</p>
            </div>
          ) : (
            tareas.map((t) => {
              const fechaLimite = t.fecha_entrega ? new Date(t.fecha_entrega) : null
              const vencida = fechaLimite && fechaLimite < new Date()
              const entrega = t.mi_entrega
              const presentada = entrega?.estado === 'entregada'
              const esBorrador = entrega?.estado === 'borrador'
              const presentandoEsta = presentando && modalSubida?.id_tarea === t.id_tarea

              const renderReemplazar = () => (
                <div className="mt-2 flex flex-wrap justify-end gap-2">
                  <label className="inline-block cursor-pointer rounded-lg bg-primary-100 px-3 py-1.5 text-[10px] font-bold text-primary transition-all hover:bg-primary-200 dark:bg-primary-900/30 dark:text-primary-300">
                    Reemplazar archivo
                    <input ref={(el) => fileInputRefs.current[t.id_tarea] = el} type="file" accept={FORMATOS_ACEPTADOS} className="hidden" onChange={(e) => { if (e.target.files[0]) handleSubir(t, e.target.files[0]) }} />
                  </label>
                  {t.permitir_link && (
                    <button onClick={() => abrirModalLink(t)} className="rounded-lg bg-primary-100 px-3 py-1.5 text-[10px] font-bold text-primary transition-all hover:bg-primary-200 dark:bg-primary-900/30 dark:text-primary-300">
                      Reemplazar enlace
                    </button>
                  )}
                </div>
              )

              const renderDetalle = (etiqueta, cls) => (
                <div className="text-right">
                  <span className={cls}>{etiqueta}</span>
                  {entrega.nombre_original && (
                    <p className="mt-1 max-w-[180px] truncate text-[10px] text-neutral-400" title={entrega.nombre_original}>{entrega.nombre_original}</p>
                  )}
                  {entrega.link && (
                    <a href={entrega.link} target="_blank" rel="noreferrer" className="mt-1 inline-block max-w-[180px] truncate text-[10px] font-bold text-primary underline hover:text-primary-accent-300" title={entrega.link}>
                      {entrega.link}
                    </a>
                  )}
                  {entrega.calificacion !== null && (
                    <div className="mt-2 text-xl font-extrabold text-primary">{entrega.calificacion} pts</div>
                  )}
                </div>
              )

              return (
                <div key={t.id_tarea} className={`rounded-xl bg-white p-6 shadow-4 dark:bg-surface-dark ${vencida && !presentada && !esBorrador ? 'border-l-4 border-danger' : presentada ? 'border-l-4 border-success' : esBorrador ? 'border-l-4 border-warning' : 'border-l-4 border-primary'}`}>
                  <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="min-w-0 flex-1">
                      <div className="flex flex-wrap items-center gap-2">
                        <h3 className="text-lg font-bold text-neutral-800 dark:text-neutral-100">{t.titulo}</h3>
                        {t.unidad && (
                          <span className="rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-semibold text-primary dark:bg-primary-900/30 dark:text-primary-300">
                            {t.unidad.numero_semana ? `Semana ${t.unidad.numero_semana}` : ''}{t.unidad.titulo ? ` · ${t.unidad.titulo}` : ''}
                          </span>
                        )}
                      </div>
                      {t.descripcion && <p className="mt-1 whitespace-pre-line text-sm text-neutral-500 dark:text-neutral-400">{t.descripcion}</p>}
                      {fechaLimite && (
                        <p className={`mt-2 text-xs font-medium ${vencida ? 'text-danger' : 'text-neutral-400'}`}>
                          Límite: {fechaLimite.toLocaleString('es-GT')}
                          {vencida && !presentada && <span className="ml-2 font-bold">— TIEMPO AGOTADO —</span>}
                        </p>
                      )}
                    </div>
                    <div className="shrink-0">
                      {presentada ? (
                        <div>
                          {renderDetalle('Entregado', badge.success)}
                          {!vencida && renderReemplazar()}
                        </div>
                      ) : esBorrador ? (
                        <div>
                          {renderDetalle('Borrador', badge.warning)}
                          <div className="mt-2 flex flex-col items-end gap-1.5">
                            <button
                              onClick={() => presentarEntrega(entrega.id_entrega, entrega.nombre_original || entrega.link, entrega.link ? 'enlace' : 'archivo')}
                              disabled={presentando}
                              className={`${btn.primary} disabled:opacity-50`}
                            >
                              {presentandoEsta ? 'Presentando...' : 'Presentar Tarea'}
                            </button>
                            <p className="text-[9px] text-neutral-400">Tu docente verá la tarea al presentarla.</p>
                          </div>
                          {!vencida && renderReemplazar()}
                        </div>
                      ) : vencida ? (
                        <div className="text-right">
                          <span className={badge.danger}>BLOQUEADA</span>
                          <p className="mt-1 text-[10px] text-danger">Su tiempo ya pasó</p>
                        </div>
                      ) : (
                        <div className="flex flex-col items-end gap-2">
                          <label className={`${btn.primary} cursor-pointer`}>
                            Subir archivo
                            <input ref={(el) => fileInputRefs.current[t.id_tarea] = el} type="file" accept={FORMATOS_ACEPTADOS} className="hidden" onChange={(e) => { if (e.target.files[0]) handleSubir(t, e.target.files[0]) }} />
                          </label>
                          {t.permitir_link && (
                            <button onClick={() => abrirModalLink(t)} className={btn.secondary}>
                              Enviar enlace
                            </button>
                          )}
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              )
            })
          )}
        </div>
      )}

      {tab === 'materiales' && (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          {materiales.length === 0 ? (
            <div className="rounded-xl bg-white p-12 text-center shadow-4 dark:bg-surface-dark md:col-span-2">
              <p className="text-sm text-neutral-400 dark:text-neutral-500">El catedrático aún no ha compartido materiales.</p>
            </div>
          ) : (
            materiales.map((m) => (
              <div key={m.id_material} className="rounded-xl bg-white p-5 shadow-4 dark:bg-surface-dark">
                <div className="flex items-start justify-between gap-3">
                  <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                      <h3 className="text-base font-bold text-neutral-800 dark:text-neutral-100">{m.titulo}</h3>
                      <span className={m.tipo === 'archivo' ? badge.info : badge.warning}>
                        {m.tipo === 'archivo' ? 'Archivo' : 'Enlace'}
                      </span>
                    </div>
                    {m.id_unidad && <p className="mt-1 text-xs font-semibold text-primary">Material de esta semana</p>}
                    {m.descripcion && <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{m.descripcion}</p>}
                    <p className="mt-1 text-[11px] text-neutral-400">
                      {m.fecha_publicacion ? new Date(m.fecha_publicacion).toLocaleString('es-GT') : ''}
                    </p>
                  </div>
                  {m.tipo === 'archivo' ? (
                    <button onClick={() => descargarMaterial(m)} className={`${btn.outline} shrink-0 !px-3 !py-1.5`}>
                      Descargar
                    </button>
                  ) : (
                    <a href={m.url} target="_blank" rel="noreferrer" className={`${btn.outline} shrink-0 !px-3 !py-1.5`}>Abrir</a>
                  )}
                </div>
              </div>
            ))
          )}
        </div>
      )}

      {tab === 'evaluaciones' && (
        <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
          <div className="border-b border-neutral-100 bg-neutral-50 px-6 py-4 dark:border-neutral-600 dark:bg-neutral-700/50">
            <h2 className="text-xl font-bold text-neutral-800 dark:text-neutral-100">Actividades evaluadas</h2>
          </div>
          <div className="p-6">
            {evaluaciones.length === 0 ? (
              <div className="py-10 text-center text-sm text-neutral-400 dark:text-neutral-500">
                El catedrático aún no ha registrado actividades evaluadas.
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-neutral-200 bg-neutral-50 text-left text-xs font-bold uppercase tracking-wider text-neutral-500 dark:border-neutral-600 dark:bg-neutral-700/50 dark:text-neutral-400">
                      <th className="px-4 py-3">Zona</th>
                      <th className="px-4 py-3">Actividad</th>
                      <th className="px-4 py-3">Unidad</th>
                      <th className="px-4 py-3 text-right">Puntos</th>
                    </tr>
                  </thead>
                  <tbody>
                    {(zonas || []).map((zona) => (
                      zona.evaluaciones.map((ev, i) => (
                        <tr key={ev.id_evaluacion} className="border-b border-neutral-100 dark:border-neutral-700">
                          {i === 0 && (
                            <td rowSpan={zona.evaluaciones.length} className="px-4 py-3 align-top">
                              <span className="inline-block rounded-full bg-primary px-2.5 py-0.5 text-xs font-bold text-white">
                                {zona.nombre} · {zona.puntos} pts
                              </span>
                            </td>
                          )}
                          <td className="px-4 py-3 font-semibold text-neutral-700 dark:text-neutral-200">{ev.nombre}</td>
                          <td className="px-4 py-3 text-neutral-500 dark:text-neutral-300">{ev.unidad_academica ? `Unidad ${ev.unidad_academica}` : '—'}</td>
                          <td className="px-4 py-3 text-right font-bold text-primary">{ev.porcentaje} pts</td>
                        </tr>
                      ))
                    ))}
                    {evaluaciones.filter((ev) => !ev.id_zona).map((ev) => (
                      <tr key={ev.id_evaluacion} className="border-b border-neutral-100 dark:border-neutral-700">
                        <td className="px-4 py-3 align-top">
                          <span className="inline-block rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs font-bold text-neutral-500 dark:bg-neutral-700 dark:text-neutral-300">
                            Sin zona
                          </span>
                        </td>
                        <td className="px-4 py-3 font-semibold text-neutral-700 dark:text-neutral-200">{ev.nombre}</td>
                        <td className="px-4 py-3 text-neutral-500 dark:text-neutral-300">{ev.unidad_academica ? `Unidad ${ev.unidad_academica}` : '—'}</td>
                        <td className="px-4 py-3 text-right font-bold text-primary">{ev.porcentaje} pts</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>
      )}

      {tab === 'anuncios' && (
        <div className="space-y-4">
          {anuncios.length === 0 ? (
            <div className="rounded-xl bg-white p-12 text-center shadow-4 dark:bg-surface-dark">
              <p className="text-sm text-neutral-400 dark:text-neutral-500">El catedrático aún no ha publicado anuncios.</p>
            </div>
          ) : (
            anuncios.map((a) => (
              <div key={a.id_anuncio} className="rounded-xl bg-white p-6 shadow-4 dark:bg-surface-dark">
                <div className="flex items-start gap-3">
                  <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary">
                    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
                  </div>
                  <div className="min-w-0 flex-1">
                    <h3 className="text-base font-bold text-neutral-800 dark:text-neutral-100">{a.titulo}</h3>
                    {a.contenido && <p className="mt-1 whitespace-pre-line text-sm text-neutral-500 dark:text-neutral-400">{a.contenido}</p>}
                    <p className="mt-2 text-[11px] font-semibold text-primary">
                      {a.fecha_publicacion ? new Date(a.fecha_publicacion).toLocaleString('es-GT') : ''}
                    </p>
                  </div>
                </div>
              </div>
            ))
          )}
        </div>
      )}

      <Modal
        open={!!modalLinkTarea}
        onClose={() => setModalLinkTarea(null)}
        title="Entregar tarea por enlace"
        size="sm"
        footer={
          <>
            <button onClick={() => setModalLinkTarea(null)} className={btn.neutral}>Cancelar</button>
            <button onClick={handleEnviarLink} disabled={enviandoLink} className={`${btn.primary} disabled:opacity-50`}>
              {enviandoLink ? 'Enviando...' : 'Enviar enlace'}
            </button>
          </>
        }
      >
        <div>
          <label className="mb-1.5 block text-sm font-medium text-neutral-700 dark:text-neutral-300">Enlace de tu entrega</label>
          <input
            type="url"
            value={linkValue}
            onChange={(e) => setLinkValue(e.target.value)}
            placeholder="https://drive.google.com/..."
            className="w-full rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm text-neutral-700 shadow-2 transition duration-150 ease-in-out placeholder:text-neutral-400 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-200"
          />
          <p className="mt-1.5 text-xs text-neutral-400 dark:text-neutral-500">Ej. un enlace a Google Drive, Dropbox, GitHub, etc.</p>
        </div>
      </Modal>

      <Modal
        open={!!modalSubida}
        onClose={() => { if (modalSubida?.fase === 'subido') setModalSubida(null) }}
        title={modalSubida?.fase === 'subiendo' ? 'Subiendo documento' : 'Documento subido'}
        size="sm"
        footer={
          modalSubida?.fase === 'subido' ? (
            <>
              <button type="button" onClick={() => setModalSubida(null)} className={btn.neutral}>Cerrar</button>
              <button
                type="button"
                onClick={() => presentarEntrega(modalSubida?.id_entrega, modalSubida?.nombre, modalSubida?.tipo)}
                disabled={presentando}
                className={`${btn.primary} disabled:opacity-50`}
              >
                {presentando ? 'Presentando...' : 'Presentar Tarea'}
              </button>
            </>
          ) : null
        }
      >
        {modalSubida ? (
          modalSubida.fase === 'subiendo' ? (
            <div className="text-center">
              <div className="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
              <p className="text-sm font-bold text-neutral-700 dark:text-neutral-200">Subiendo documento...</p>
              <div className="mx-auto mt-4 w-full max-w-xs bg-neutral-100 dark:bg-neutral-700 rounded-full h-2.5 overflow-hidden">
                <div className="bg-primary h-full rounded-full transition-all duration-300" style={{ width: `${modalSubida.progreso}%` }}></div>
              </div>
              <span className="mt-1 block text-[10px] font-bold text-primary">{modalSubida.progreso}%</span>
              <p className="mt-2 text-xs text-neutral-400 break-all">{modalSubida.nombre}</p>
            </div>
          ) : (
            <div className="text-center">
              <div className="w-16 h-16 bg-success-100 dark:bg-success-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg className="w-8 h-8 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
              </div>
              <p className="text-base font-bold text-neutral-800 dark:text-neutral-100">Documento subido</p>
              <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400 break-all">{modalSubida.nombre}</p>
              <p className="mt-3 text-xs text-neutral-400">
                Pulsa <span className="font-bold text-primary">Presentar Tarea</span> para que tu docente pueda visualizarla.
              </p>
            </div>
          )
        ) : null}
      </Modal>

      {successModal && (
      <Modal
        open
        onClose={() => setSuccessModal(null)}
        title="¡Tarea presentada!"
        size="sm"
        footer={
          <button type="button" onClick={() => setSuccessModal(null)} className={`${btn.primary} w-full`}>
            Aceptar
          </button>
        }
      >
        <div className="text-center">
          <div className="w-16 h-16 bg-success-100 dark:bg-success-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg className="w-8 h-8 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
          </div>
          <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-1">
            {successModal.tipo === 'enlace' ? 'Se ha presentado el enlace de tu entrega:' : 'Se ha presentado tu documento:'}
          </p>
          <p className="text-sm font-bold text-primary mb-2 break-all">{successModal.nombre}</p>
          <p className="text-xs text-neutral-400">Tu docente ya puede visualizar la tarea.</p>
        </div>
      </Modal>
      )}

      <Modal
        open={!!alertMessage}
        onClose={() => setAlertMessage(null)}
        title="Aviso"
        size="sm"
        footer={
          <button onClick={() => setAlertMessage(null)} className={`${btn.primary} w-full`}>
            Aceptar
          </button>
        }
      >
        <p className="whitespace-pre-line text-center text-sm text-neutral-600 dark:text-neutral-300">{alertMessage}</p>
      </Modal>
    </div>
  )
}
