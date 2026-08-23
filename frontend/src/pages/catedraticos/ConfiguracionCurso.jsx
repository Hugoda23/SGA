import { useState, useEffect, useCallback } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import api from '../../api/axios'
import { btn, input, card, badge } from '../../lib/twClasses'
import Modal from '../../components/Modal'
import MaterialesTab from './curso/MaterialesTab'
import AnunciosTab from './curso/AnunciosTab'
import EvaluacionesTab from './curso/EvaluacionesTab'

const ESTADOS = ['planificado', 'en_progreso', 'completado']
const SIGUIENTE_ESTADO = { planificado: 'en_progreso', en_progreso: 'completado', completado: 'planificado' }

const estadoBadge = {
  planificado: badge.info,
  en_progreso: badge.warning,
  completado: badge.success,
}

const estadoIcono = {
  planificado: '⭘',
  en_progreso: '▶',
  completado: '✓',
}

const estadoColor = {
  planificado: 'border-neutral-300 text-neutral-400',
  en_progreso: 'border-warning bg-warning text-white',
  completado: 'border-success bg-success text-white',
}

const estadoLabel = {
  planificado: 'Planificado',
  en_progreso: 'En progreso',
  completado: 'Completado',
}

const inicialUnidad = {
  numero_semana: '',
  titulo: '',
  temas: '',
  competencia: '',
  estado: 'planificado',
  fecha_inicio: '',
  fecha_fin: '',
}

export default function ConfiguracionCurso() {
  const { id_asignacion } = useParams()
  const navigate = useNavigate()
  const [data, setData] = useState(null)
  const [tareas, setTareas] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [tab, setTab] = useState('avance')

  const [titulo, setTitulo] = useState('')
  const [descripcion, setDescripcion] = useState('')
  const [puntos, setPuntos] = useState('')
  const [idZonaTarea, setIdZonaTarea] = useState('')
  const [fechaEntrega, setFechaEntrega] = useState('')
  const [horaEntrega, setHoraEntrega] = useState('')
  const [idUnidadTarea, setIdUnidadTarea] = useState('')
  const [permitirLink, setPermitirLink] = useState(false)
  const [publicando, setPublicando] = useState(false)

  const [modalUnidad, setModalUnidad] = useState(false)
  const [unidadForm, setUnidadForm] = useState(inicialUnidad)
  const [editandoUnidad, setEditandoUnidad] = useState(null)
  const [guardandoUnidad, setGuardandoUnidad] = useState(false)

  const [confirmGenerar, setConfirmGenerar] = useState(false)
  const [generando, setGenerando] = useState(false)
  const [unidadEliminar, setUnidadEliminar] = useState(null)
  const [eliminando, setEliminando] = useState(false)

  const [descargandoPDF, setDescargandoPDF] = useState(false)
  const [alertMessage, setAlertMessage] = useState(null)

  const cargarTodo = useCallback(async () => {
    const [resConfig, resTareas] = await Promise.all([
      api.get(`/v1/catedratico/configuracion-curso/${id_asignacion}`),
      api.get(`/v1/tareas/por-asignacion/${id_asignacion}`),
    ])
    setData(resConfig.data)
    setTareas(resTareas.data)
  }, [id_asignacion])

  useEffect(() => {
    cargarTodo()
      .catch((err) => setError(err.response?.data?.error || 'Error al cargar el curso'))
      .finally(() => setLoading(false))
  }, [cargarTodo])

  const setCampoUnidad = (campo, valor) => setUnidadForm((f) => ({ ...f, [campo]: valor }))

  const abrirUnidadNueva = () => {
    const semanas = (data?.unidades || []).map((u) => u.numero_semana).filter((n) => n != null)
    setEditandoUnidad(null)
    setUnidadForm({ ...inicialUnidad, numero_semana: semanas.length ? Math.max(...semanas) + 1 : 1 })
    setModalUnidad(true)
  }

  const abrirUnidadEditar = (u) => {
    setEditandoUnidad(u.id_unidad)
    setUnidadForm({
      numero_semana: u.numero_semana ?? '',
      titulo: u.titulo || '',
      temas: u.temas || '',
      competencia: u.competencia || '',
      estado: u.estado || 'planificado',
      fecha_inicio: u.fecha_inicio || '',
      fecha_fin: u.fecha_fin || '',
    })
    setModalUnidad(true)
  }

  const guardarUnidad = async () => {
    if (!unidadForm.titulo.trim()) { setAlertMessage('El título de la unidad/semana es obligatorio'); return }
    setGuardandoUnidad(true)
    try {
      const payload = {
        id_asignacion: parseInt(id_asignacion),
        numero_semana: unidadForm.numero_semana ? parseInt(unidadForm.numero_semana) : null,
        titulo: unidadForm.titulo.trim(),
        temas: unidadForm.temas.trim() || null,
        competencia: unidadForm.competencia.trim() || null,
        estado: unidadForm.estado,
        fecha_inicio: unidadForm.fecha_inicio || null,
        fecha_fin: unidadForm.fecha_fin || null,
      }
      if (editandoUnidad) {
        await api.put(`/v1/unidades/${editandoUnidad}`, payload)
      } else {
        await api.post('/v1/unidades', payload)
      }
      setModalUnidad(false)
      cargarTodo()
    } catch (err) {
      setAlertMessage(err.response?.data?.message || 'Error al guardar la unidad')
    } finally {
      setGuardandoUnidad(false)
    }
  }

  const avanzarEstado = async (u) => {
    const siguiente = SIGUIENTE_ESTADO[u.estado] || 'planificado'
    try {
      await api.patch(`/v1/unidades/${u.id_unidad}`, { estado: siguiente })
      cargarTodo()
    } catch (err) {
      setAlertMessage(err.response?.data?.message || 'Error al actualizar el estado')
    }
  }

  const eliminarUnidad = async () => {
    if (!unidadEliminar) return
    setEliminando(true)
    try {
      await api.delete(`/v1/unidades/${unidadEliminar}`)
      setUnidadEliminar(null)
      cargarTodo()
    } catch (err) {
      setAlertMessage(err.response?.data?.message || 'Error al eliminar la unidad')
    } finally {
      setEliminando(false)
    }
  }

  const generar16Semanas = async () => {
    setGenerando(true)
    try {
      const existentes = (data?.unidades || []).length
      for (let i = 1; i <= 16; i++) {
        await api.post('/v1/unidades', {
          id_asignacion: parseInt(id_asignacion),
          numero_semana: existentes + i,
          titulo: `Semana ${existentes + i}`,
          temas: null,
          competencia: null,
          estado: 'planificado',
          fecha_inicio: null,
          fecha_fin: null,
        })
      }
      setConfirmGenerar(false)
      setAlertMessage('Se generaron 16 semanas del avance programático.')
      cargarTodo()
    } catch (err) {
      setAlertMessage(err.response?.data?.message || 'Error al generar las semanas')
    } finally {
      setGenerando(false)
    }
  }

  const handlePublicarTarea = async () => {
    if (!titulo.trim()) { setAlertMessage('El título de la tarea es obligatorio'); return }
    if (idZonaTarea) {
      const zonaSeleccionada = zonasConDisponible.find((z) => z.id_zona === parseInt(idZonaTarea))
      const puntosNum = puntos !== '' ? parseFloat(puntos) : null
      if (puntosNum === null) { setAlertMessage('Indica los puntos de la tarea para asignarla a una zona'); return }
      if (zonaSeleccionada && puntosNum > zonaSeleccionada.disponible) {
        setAlertMessage(`La zona "${zonaSeleccionada.nombre}" solo tiene ${zonaSeleccionada.disponible} pts disponibles.`)
        return
      }
    }
    setPublicando(true)
    try {
      const payload = {
        titulo: titulo.trim(),
        descripcion: descripcion.trim() || null,
        puntos: puntos !== '' ? parseFloat(puntos) : null,
        id_zona: idZonaTarea ? parseInt(idZonaTarea) : null,
        id_asignacion: parseInt(id_asignacion),
        id_unidad: idUnidadTarea ? parseInt(idUnidadTarea) : null,
        permitir_link: permitirLink,
      }
      if (fechaEntrega) {
        payload.fecha_entrega = horaEntrega
          ? `${fechaEntrega} ${horaEntrega}:00`
          : `${fechaEntrega} 23:59:59`
      }
      await api.post('/v1/tareas', payload)
      setAlertMessage('Tarea publicada correctamente. Los alumnos recibirán una notificación.')
      setTitulo(''); setDescripcion(''); setPuntos(''); setIdZonaTarea(''); setFechaEntrega(''); setHoraEntrega(''); setIdUnidadTarea(''); setPermitirLink(false)
      cargarTodo()
    } catch (err) {
      setAlertMessage(err.response?.data?.message || err.response?.data?.errors?.puntos?.[0] || 'Error al publicar tarea')
    } finally {
      setPublicando(false)
    }
  }

  const descargarPDF = async () => {
    setDescargandoPDF(true)
    try {
      const res = await api.get(`/v1/reportes/pdf/avance-programatico/${id_asignacion}`, {
        responseType: 'blob',
      })
      const url = URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `avance_programatico_${id_asignacion}.pdf`)
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      URL.revokeObjectURL(url)
    } catch {
      setAlertMessage('Error al descargar el PDF')
    } finally {
      setDescargandoPDF(false)
    }
  }

  const tabs = [
    { id: 'avance', label: 'Avance Programático' },
    { id: 'tareas', label: 'Tareas' },
    { id: 'materiales', label: 'Materiales' },
    { id: 'anuncios', label: 'Anuncios' },
    { id: 'evaluaciones', label: 'Evaluaciones' },
  ]

  if (loading) return (
    <div className="mx-auto max-w-7xl pb-12">
      <div className="flex flex-col items-center gap-3 py-16 text-neutral-500">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
        <span className="text-lg font-semibold">Cargando configuración del curso...</span>
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

  const { asignacion, unidades, horarios, alumnos, materiales, anuncios, evaluaciones, zonas, total_puntos_zonas } = data

  // Puntos disponibles por zona: puntos de la zona menos lo que ya consumen
  // sus actividades (evaluaciones) y las demás tareas ya asignadas a ella.
  const zonasConDisponible = (zonas || []).map((z) => {
    const consumidoEvaluaciones = (z.evaluaciones || []).reduce((acc, ev) => acc + (parseFloat(ev.porcentaje) || 0), 0)
    const consumidoTareas = tareas
      .filter((t) => t.id_zona === z.id_zona)
      .reduce((acc, t) => acc + (parseFloat(t.puntos) || 0), 0)
    const disponible = (parseFloat(z.puntos) || 0) - consumidoEvaluaciones - consumidoTareas
    return { ...z, disponible }
  })

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
            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            Configuración del Curso
          </div>
          <h1 className="text-3xl font-bold text-neutral-800 dark:text-neutral-100">{asignacion.curso}</h1>
          <p className="mt-1 text-sm font-medium text-neutral-500 dark:text-neutral-400">
            {asignacion.grado} &quot;{asignacion.seccion}&quot; · {asignacion.periodo}
          </p>
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

      {/* Resumen del curso */}
      <div className="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div className="rounded-xl bg-white p-4 shadow-4 dark:bg-surface-dark">
          <p className="text-xs font-semibold uppercase tracking-wide text-neutral-400 dark:text-neutral-500">Período</p>
          <p className="mt-1 text-lg font-bold text-neutral-800 dark:text-neutral-100">{asignacion.periodo}</p>
        </div>
        <div className="rounded-xl bg-white p-4 shadow-4 dark:bg-surface-dark">
          <p className="text-xs font-semibold uppercase tracking-wide text-neutral-400 dark:text-neutral-500">Horarios</p>
          <p className="mt-1 text-lg font-bold text-neutral-800 dark:text-neutral-100">
            {horarios.length ? `${horarios.length} clase(s)` : '—'}
          </p>
          <p className="text-xs text-neutral-400">
            {horarios.slice(0, 2).map((h) => `${h.dia_semana} ${h.hora_inicio}`).join(', ')}
          </p>
        </div>
        <div className="rounded-xl bg-white p-4 shadow-4 dark:bg-surface-dark">
          <p className="text-xs font-semibold uppercase tracking-wide text-neutral-400 dark:text-neutral-500">Alumnos</p>
          <p className="mt-1 text-lg font-bold text-neutral-800 dark:text-neutral-100">{alumnos.length}</p>
        </div>
        <div className="rounded-xl bg-white p-4 shadow-4 dark:bg-surface-dark">
          <p className="text-xs font-semibold uppercase tracking-wide text-neutral-400 dark:text-neutral-500">Semanas programadas</p>
          <p className="mt-1 text-lg font-bold text-neutral-800 dark:text-neutral-100">{unidades.length}</p>
        </div>
      </div>

      {tab === 'avance' && (
        <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
          <div className="flex flex-wrap items-center justify-between gap-4 border-b border-neutral-100 bg-neutral-50 px-6 py-4 dark:border-neutral-600 dark:bg-neutral-700/50">
            <h2 className="text-xl font-bold text-neutral-800 dark:text-neutral-100">Avance Programático</h2>
            <div className="flex flex-wrap gap-2">
              <button onClick={abrirUnidadNueva} className={btn.primary}>
                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                Agregar semana
              </button>
              <button onClick={() => setConfirmGenerar(true)} disabled={unidades.length > 0} className={`${btn.outline} disabled:opacity-40`} title={unidades.length > 0 ? 'Ya existen semanas programadas' : 'Genera el avance completo de 16 semanas'}>
                Generar 16 semanas
              </button>
              <button onClick={descargarPDF} disabled={descargandoPDF} className={`${btn.success} disabled:opacity-50`}>
                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                {descargandoPDF ? 'Generando...' : 'Descargar PDF'}
              </button>
            </div>
          </div>

          <div className="p-8">
            {unidades.length === 0 ? (
              <div className="py-10 text-center">
                <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary-50 text-primary dark:bg-primary-900/30">
                  <svg className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <h3 className="text-lg font-bold text-neutral-700 dark:text-neutral-200">Aún no has programado unidades</h3>
                <p className="mt-1 text-sm text-neutral-400 dark:text-neutral-500">
                  Agrega semana por semana o genera automáticamente las 16 semanas del avance.
                </p>
              </div>
            ) : (
              <div className="relative ml-4 space-y-10 border-l-2 border-neutral-100 py-2 pl-8 dark:border-neutral-600">
                {unidades.map((u) => {
                  const vencidas = u.tareas.filter((t) => t.fecha_entrega && new Date(t.fecha_entrega) < new Date()).length
                  return (
                    <div key={u.id_unidad} className="relative">
                      <div className={`absolute -left-[45px] top-0 flex h-7 w-7 items-center justify-center rounded-full border-2 text-sm font-bold ring-4 ring-white dark:ring-surface-dark ${estadoColor[u.estado] || estadoColor.planificado}`}>
                        {estadoIcono[u.estado] || '⭘'}
                      </div>
                      <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="min-w-0">
                          <div className="flex flex-wrap items-center gap-2">
                            <h3 className="text-lg font-bold text-neutral-800 dark:text-neutral-100">{u.titulo}</h3>
                            <span className={estadoBadge[u.estado] || badge.info}>{estadoLabel[u.estado] || 'Planificado'}</span>
                            {u.numero_semana && (
                              <span className="rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs font-semibold text-neutral-500 dark:bg-neutral-700 dark:text-neutral-300">
                                Semana {u.numero_semana}
                              </span>
                            )}
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
                              {u.tareas.map((t) => (
                                <span key={t.id_tarea} className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ${t.total_entregas > 0 ? 'bg-success-50 text-success dark:bg-success-900/30 dark:text-success-300' : 'bg-neutral-100 text-neutral-500 dark:bg-neutral-700 dark:text-neutral-300'}`}>
                                  {t.titulo} · {t.total_entregas} entrega(s)
                                </span>
                              ))}
                            </div>
                          )}
                        </div>
                        <div className="flex shrink-0 gap-2">
                          <button onClick={() => avanzarEstado(u)} className={`${btn.ghost} !px-3 !py-1.5`} title={`Marcar como ${SIGUIENTE_ESTADO[u.estado]?.replace('_', ' ') || 'planificado'}`}>
                            {SIGUIENTE_ESTADO[u.estado] === 'completado' ? 'Marcar completado' : 'Avanzar estado'}
                          </button>
                          <button onClick={() => abrirUnidadEditar(u)} className={`${btn.outline} !px-3 !py-1.5`}>Editar</button>
                          <button onClick={() => setUnidadEliminar(u.id_unidad)} className={`${btn.outlineDanger} !px-3 !py-1.5`}>Eliminar</button>
                        </div>
                      </div>
                      {vencidas > 0 && (
                        <p className="mt-2 text-xs font-semibold text-danger">{vencidas} tarea(s) vencida(s) en esta semana</p>
                      )}
                    </div>
                  )
                })}
              </div>
            )}
          </div>
        </div>
      )}

      {tab === 'tareas' && (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <div className="flex flex-col overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
            <div className="border-b border-primary-100/50 bg-primary-50 p-6 dark:border-primary-900/50 dark:bg-primary-900/20">
              <h2 className="flex items-center gap-2 text-xl font-bold text-primary">
                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                Nueva Tarea
              </h2>
            </div>
            <div className="flex flex-1 flex-col gap-5 p-6">
              <div>
                <label className={input.label}>Título de la Tarea</label>
                <input type="text" value={titulo} onChange={(e) => setTitulo(e.target.value)} placeholder="Ej. Ejercicios de Derivadas" className={input.base} />
              </div>
              <div>
                <label className={input.label}>Descripción / Instrucciones</label>
                <textarea rows="3" value={descripcion} onChange={(e) => setDescripcion(e.target.value)} placeholder="Escribe las instrucciones detalladas aquí..." className={`${input.base} resize-none`}></textarea>
              </div>
              <div>
                <label className={input.label}>Semana / Unidad vinculada (opcional)</label>
                <select value={idUnidadTarea} onChange={(e) => setIdUnidadTarea(e.target.value)} className={input.base}>
                  <option value="">Sin vincular a una semana</option>
                  {unidades.map((u) => (
                    <option key={u.id_unidad} value={u.id_unidad}>
                      {u.numero_semana ? `Semana ${u.numero_semana} — ` : ''}{u.titulo}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className={input.label}>Puntos que vale la tarea (opcional)</label>
                <input type="number" min="0" max="1000" step="0.01" value={puntos} onChange={(e) => setPuntos(e.target.value)} placeholder="Ej. 10" className={input.base} />
                <p className="mt-1 text-xs text-neutral-400 dark:text-neutral-500">Si no indicas puntos, la calificación de las entregas se registrará sobre 100.</p>
              </div>
              <div>
                <label className={input.label}>Zona de evaluación (opcional)</label>
                <select value={idZonaTarea} onChange={(e) => setIdZonaTarea(e.target.value)} className={input.base}>
                  <option value="">Sin vincular a una zona</option>
                  {zonasConDisponible.map((z) => (
                    <option key={z.id_zona} value={z.id_zona} disabled={z.disponible <= 0}>
                      {z.nombre} — {z.disponible > 0 ? `${z.disponible} pts disponibles` : 'sin puntos disponibles'}
                    </option>
                  ))}
                </select>
                <p className="mt-1 text-xs text-neutral-400 dark:text-neutral-500">
                  Si vinculas la tarea a una zona, sus puntos se descuentan del presupuesto de esa zona — no se puede exceder lo disponible.
                </p>
              </div>
              <div>
                <label className={input.label}>Fecha de Entrega</label>
                <input type="date" value={fechaEntrega} onChange={(e) => setFechaEntrega(e.target.value)} className={input.base} />
              </div>
              <div>
                <label className={input.label}>Hora de Entrega</label>
                <input type="time" value={horaEntrega} onChange={(e) => setHoraEntrega(e.target.value)} className={input.base} />
                <p className="mt-1 text-xs text-neutral-400 dark:text-neutral-500">Si no indicas hora, el límite será a las 23:59 del día de entrega.</p>
              </div>
              <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-neutral-200 p-4 transition-colors hover:border-primary dark:border-neutral-600">
                <input
                  type="checkbox"
                  checked={permitirLink}
                  onChange={(e) => setPermitirLink(e.target.checked)}
                  className="mt-0.5 h-4 w-4 rounded border-neutral-300 text-primary focus:ring-primary"
                />
                <span>
                  <span className="block text-sm font-semibold text-neutral-700 dark:text-neutral-200">Permitir entrega por enlace (link)</span>
                  <span className="block text-xs text-neutral-400 dark:text-neutral-500">El alumno podrá entregar la tarea con un enlace en lugar de subir un archivo.</span>
                </span>
              </label>
              <div className="mt-2 flex justify-end">
                <button onClick={handlePublicarTarea} disabled={publicando} className={`${btn.primary} disabled:opacity-50`}>
                  {publicando ? 'Publicando...' : 'Publicar Tarea'}
                </button>
              </div>
            </div>
          </div>

          <div className="flex flex-col overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
            <div className="border-b border-neutral-100 bg-neutral-50 px-6 py-4 dark:border-neutral-600 dark:bg-neutral-700/50">
              <h3 className="text-sm font-bold text-neutral-700 dark:text-neutral-200">Tareas publicadas ({tareas.length})</h3>
            </div>
            <div className="flex-1 space-y-3 p-6">
              {tareas.length === 0 ? (
                <div className="py-10 text-center text-sm text-neutral-400 dark:text-neutral-500">Aún no has publicado tareas.</div>
              ) : (
                tareas.map((t) => {
                  const vencida = t.fecha_entrega && new Date(t.fecha_entrega) < new Date()
                  const zonaTarea = t.id_zona ? zonas.find((z) => z.id_zona === t.id_zona) : null
                  return (
                    <div key={t.id_tarea} className={`rounded-xl border p-3 ${vencida ? 'border-danger-100 bg-danger-50' : 'border-neutral-100 bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-700/50'}`}>
                      <div className="flex items-center justify-between gap-3">
                        <div className="min-w-0 flex-1">
                          <div className="flex items-center gap-2">
                            <p className="truncate text-sm font-bold text-neutral-700 dark:text-neutral-200">{t.titulo}</p>
                            {t.puntos !== null && t.puntos !== undefined && (
                              <span className="shrink-0 rounded-full bg-primary-50 px-2 py-0.5 text-xs font-bold text-primary dark:bg-primary-900/30">{t.puntos} pts</span>
                            )}
                          </div>
                          <p className="mt-0.5 text-xs text-neutral-400 dark:text-neutral-500">
                            {t.fecha_entrega ? new Date(t.fecha_entrega).toLocaleString('es-GT') : 'Sin fecha límite'}
                            {vencida && <span className="ml-2 font-bold text-danger">(Vencida)</span>}
                          </p>
                          <p className="text-xs text-neutral-400 dark:text-neutral-500">{t.total_entregas}/{t.total_alumnos} entregas</p>
                          {zonaTarea && (
                            <p className="mt-1 text-xs font-semibold text-secondary">Zona: {zonaTarea.nombre}</p>
                          )}
                          {t.unidad && (
                            <p className="mt-1 text-xs font-semibold text-primary">
                              {t.unidad.numero_semana ? `Semana ${t.unidad.numero_semana} · ` : ''}{t.unidad.titulo}
                            </p>
                          )}
                        </div>
                        <button onClick={() => navigate(`/entregas-tarea?tarea_id=${t.id_tarea}`)} className="shrink-0 rounded-lg bg-primary px-3 py-1.5 text-xs font-bold text-white transition-all hover:bg-primary-accent-300">Ver entregas</button>
                      </div>
                    </div>
                  )
                })
              )}
            </div>
          </div>
        </div>
      )}

      {tab === 'materiales' && (
        <MaterialesTab
          idAsignacion={id_asignacion}
          unidades={unidades}
          materiales={materiales}
          reload={cargarTodo}
          setAlert={setAlertMessage}
        />
      )}

      {tab === 'anuncios' && (
        <AnunciosTab
          idAsignacion={id_asignacion}
          anuncios={anuncios}
          reload={cargarTodo}
          setAlert={setAlertMessage}
        />
      )}

      {tab === 'evaluaciones' && (
        <EvaluacionesTab
          idAsignacion={id_asignacion}
          zonas={zonas || []}
          evaluaciones={evaluaciones || []}
          totalPuntosZonas={total_puntos_zonas || 0}
          reload={cargarTodo}
          setAlert={setAlertMessage}
        />
      )}

      {/* Modal Unidad */}
      <Modal
        open={modalUnidad}
        onClose={() => setModalUnidad(false)}
        title={editandoUnidad ? 'Editar unidad / semana' : 'Agregar unidad / semana'}
        size="lg"
        scrollable
        footer={
          <>
            <button onClick={() => setModalUnidad(false)} className={btn.neutral}>Cancelar</button>
            <button onClick={guardarUnidad} disabled={guardandoUnidad} className={`${btn.primary} disabled:opacity-50`}>
              {guardandoUnidad ? 'Guardando...' : editandoUnidad ? 'Guardar cambios' : 'Crear unidad'}
            </button>
          </>
        }
      >
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label className={input.label}>Número de semana</label>
            <input type="number" min="1" value={unidadForm.numero_semana} onChange={(e) => setCampoUnidad('numero_semana', e.target.value)} className={input.base} />
          </div>
          <div>
            <label className={input.label}>Estado</label>
            <select value={unidadForm.estado} onChange={(e) => setCampoUnidad('estado', e.target.value)} className={input.base}>
              {ESTADOS.map((e) => <option key={e} value={e}>{estadoLabel[e]}</option>)}
            </select>
          </div>
          <div className="sm:col-span-2">
            <label className={input.label}>Título de la unidad / tema central</label>
            <input type="text" value={unidadForm.titulo} onChange={(e) => setCampoUnidad('titulo', e.target.value)} placeholder="Ej. Álgebra Lineal" className={input.base} />
          </div>
          <div className="sm:col-span-2">
            <label className={input.label}>Temas</label>
            <textarea rows="3" value={unidadForm.temas} onChange={(e) => setCampoUnidad('temas', e.target.value)} placeholder="Desglosa los temas de la semana" className={`${input.base} resize-none`}></textarea>
          </div>
          <div className="sm:col-span-2">
            <label className={input.label}>Competencia a desarrollar</label>
            <textarea rows="2" value={unidadForm.competencia} onChange={(e) => setCampoUnidad('competencia', e.target.value)} placeholder="Competencia esperada de la unidad" className={`${input.base} resize-none`}></textarea>
          </div>
          <div>
            <label className={input.label}>Fecha inicio</label>
            <input type="date" value={unidadForm.fecha_inicio} onChange={(e) => setCampoUnidad('fecha_inicio', e.target.value)} className={input.base} />
          </div>
          <div>
            <label className={input.label}>Fecha fin</label>
            <input type="date" value={unidadForm.fecha_fin} onChange={(e) => setCampoUnidad('fecha_fin', e.target.value)} className={input.base} />
          </div>
        </div>
      </Modal>

      {/* Modal confirmar generación de 16 semanas */}
      <Modal
        open={confirmGenerar}
        onClose={() => setConfirmGenerar(false)}
        title="Generar 16 semanas"
        size="sm"
        footer={
          <>
            <button onClick={() => setConfirmGenerar(false)} className={btn.neutral}>Cancelar</button>
            <button onClick={generar16Semanas} disabled={generando} className={`${btn.primary} disabled:opacity-50`}>
              {generando ? 'Generando...' : 'Generar'}
            </button>
          </>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">
          Se crearán 16 unidades con el título <strong>Semana 1</strong> a <strong>Semana 16</strong>, todas en estado
          &quot;Planificado&quot;. Podrás editarlas y agregar temas después.
        </p>
      </Modal>

      {/* Modal confirmar eliminación */}
      <Modal
        open={!!unidadEliminar}
        onClose={() => setUnidadEliminar(null)}
        title="Eliminar unidad"
        size="sm"
        footer={
          <>
            <button onClick={() => setUnidadEliminar(null)} className={btn.neutral}>Cancelar</button>
            <button onClick={eliminarUnidad} disabled={eliminando} className={`${btn.danger} disabled:opacity-50`}>
              {eliminando ? 'Eliminando...' : 'Eliminar'}
            </button>
          </>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">
          ¿Seguro que deseas eliminar esta unidad/semana? Las tareas vinculadas a ella no se eliminarán.
        </p>
      </Modal>

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
        <p className="whitespace-pre-line text-center text-sm text-neutral-600 dark:text-neutral-300">{alertMessage}</p>
      </Modal>
    </div>
  )
}
