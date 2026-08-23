import { useState, useEffect } from 'react'
import { Link, Navigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import api from '../api/axios'
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, CartesianGrid } from 'recharts'
import { btn, badge, card } from '../lib/twClasses'

export default function Dashboard() {
  const { user, hasRole } = useAuth()

  const [stats, setStats] = useState({ metrics: {}, charts: {} })
  const [resumen, setResumen] = useState(null)

  const isAdmin = hasRole('admin')
  const isAlumno = hasRole('alumno')
  const isCatedratico = hasRole('catedratico')

  const firstName = user?.alumno
    ? user.alumno.nombre.split(' ')[0]
    : user?.catedratico
    ? user.catedratico.nombre.split(' ')[0]
    : user?.username
    ? user.username.split(' ')[0]
    : 'Alejandro'

  useEffect(() => {
    const fetchData = async () => {
      try {
        if (isAdmin) {
          const resStats = await api.get('/v1/dashboard/stats')
          setStats(resStats.data)
        }

        if (isAlumno) {
          const resResumen = await api.get('/v1/alumno/resumen')
          setResumen(resResumen.data)
        }
      } catch (error) {
        console.error("Error fetching summary data", error)
      }
    }

    fetchData()
  }, [isAdmin, isAlumno])

  const getMonthAndDay = (dateStr) => {
    if (!dateStr) return null
    const date = new Date(dateStr)
    if (isNaN(date.getTime())) return null
    const month = date.toLocaleString('es-ES', { month: 'short' }).toUpperCase().replace('.', '')
    const day = date.getDate()
    return { month, day }
  }

  const formatFecha = (dateStr, opts = {}) => {
    if (!dateStr) return null
    const date = new Date(dateStr)
    if (isNaN(date.getTime())) return null
    return date.toLocaleDateString('es-ES', opts)
  }

  const promedio = resumen?.promedio_general ?? null
  const pendientes = resumen?.tareas_pendientes ?? 0
  const proximaEntrega = resumen?.proxima_entrega ?? null
  const asistencia = resumen?.asistencia_porcentaje ?? null
  const asistenciasRegistradas = resumen?.asistencias_registradas ?? 0
  const proximasEntregas = resumen?.proximas_entregas ?? []
  const avisos = resumen?.avisos ?? []

  const promedioEstado = promedio === null
    ? 'Sin calificaciones registradas'
    : promedio >= 80
    ? 'Excelente rendimiento'
    : promedio >= 60
    ? 'Rendimiento aceptable'
    : 'Necesita refuerzo'

  const asistenciaMensaje = asistencia === null
    ? 'Sin registros de asistencia'
    : asistencia >= 85
    ? 'Excelente récord de asistencia'
    : asistencia >= 70
    ? 'Buena asistencia'
    : asistencia >= 60
    ? 'Asistencia regular'
    : 'Asistencia baja'

  if (isCatedratico) {
    return <Navigate to="/mis-cursos" replace />
  }

  return (
    <div className="mx-auto max-w-7xl space-y-8 pb-12">
      {/* Title & Subtitle Greeting */}
      <div>
        <h1 className="text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-neutral-100">
          Hola, {firstName}
        </h1>
        <p className="mt-1 text-sm font-medium text-neutral-500 dark:text-neutral-400">
          Este es tu resumen académico actual.
        </p>
      </div>

      {/* Vista alumno: Mi Resumen */}
      {isAlumno && (
        <>
          {/* Summary Cards Grid */}
          <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
            {/* Card 1: Promedio General */}
            <div className="relative flex flex-col justify-between overflow-hidden rounded-xl bg-gradient-to-br from-primary to-primary-800 p-6 text-white shadow-primary-3 dark:shadow-black/30">
              <div className="flex items-center gap-2 text-sm font-bold tracking-wide">
                <span className="text-xs font-semibold uppercase tracking-wider text-primary-200">[EST]</span>
                <span>Promedio General</span>
              </div>
              <div className="my-6 flex items-baseline gap-1">
                <span className="text-5xl font-extrabold tracking-tight">{promedio ?? '—'}</span>
                <span className="text-xl font-medium text-primary-200">/100</span>
              </div>
              <div className="flex items-center gap-2">
                <span className="inline-block rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white">
                  {promedioEstado}
                </span>
              </div>
            </div>

            {/* Card 2: Tareas Pendientes */}
            <div className={`${card} flex flex-col justify-between`}>
              <div className="flex items-center justify-between gap-2 text-sm font-bold tracking-wide text-neutral-900 dark:text-neutral-100">
                <div className="flex items-center gap-2">
                  <span className="text-xs font-semibold text-neutral-600 dark:text-neutral-400">[T]</span>
                  <span>Tareas Pendientes</span>
                </div>
                {pendientes > 0
                  ? <span className={badge.warning}>{pendientes} por entregar</span>
                  : <span className={badge.success}>Al día</span>}
              </div>
              <div className="my-6">
                <span className="text-5xl font-extrabold tracking-tight text-neutral-900 dark:text-white">
                  {pendientes}
                </span>
              </div>
              <div className="text-xs font-semibold text-neutral-600 dark:text-neutral-400">
                {proximaEntrega
                  ? <>Próxima entrega: <span className="capitalize text-neutral-900 dark:text-neutral-100">{formatFecha(proximaEntrega, { day: 'numeric', month: 'long' })}</span></>
                  : 'No hay entregas próximas'}
              </div>
            </div>

            {/* Card 3: Asistencia */}
            <div className={`${card} flex flex-col justify-between`}>
              <div className="flex items-center gap-2 text-sm font-bold tracking-wide text-neutral-900 dark:text-neutral-100">
                <span className="text-xs font-semibold text-neutral-600 dark:text-neutral-400">[A]</span>
                <span>Asistencia</span>
              </div>
              <div className="my-6">
                <span className="text-5xl font-extrabold tracking-tight text-neutral-900 dark:text-white">
                  {asistencia !== null ? `${asistencia}%` : '—'}
                </span>
              </div>
              <div className="text-xs font-semibold text-neutral-600 dark:text-neutral-400">
                {asistenciaMensaje}
                {asistencia !== null && asistenciasRegistradas > 0 && (
                  <span className="ml-1 text-neutral-400">({asistenciasRegistradas} clases)</span>
                )}
              </div>
            </div>
          </div>

          {/* Avisos y Próximas Entregas */}
          <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {/* Próximas Entregas */}
            <div className={`${card}`}>
              <div className="mb-6 flex items-center justify-between">
                <h2 className="text-base font-bold text-neutral-900 dark:text-white">
                  Próximas Entregas
                </h2>
                <Link
                  to="/mis-tareas"
                  data-twe-ripple-init
                  className="text-xs font-bold text-primary transition hover:text-primary-accent-300"
                >
                  Ver Todas
                </Link>
              </div>

              {proximasEntregas.length > 0 ? (
                <div className="space-y-3">
                  {proximasEntregas.map((item) => {
                    const fecha = getMonthAndDay(item.fecha_entrega)
                    return (
                      <div
                        key={item.id_tarea}
                        className="flex items-center gap-4 rounded-md border border-neutral-100 bg-neutral-50/50 p-4 transition-colors hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800/40 dark:hover:bg-neutral-700/50"
                      >
                        <div className="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded border-2 border-neutral-800 bg-white font-sans dark:border-neutral-600 dark:bg-neutral-900">
                          <span className="text-[9px] font-extrabold uppercase leading-none text-neutral-700 dark:text-neutral-300">
                            {fecha?.month ?? '—'}
                          </span>
                          <span className="mt-0.5 text-sm font-extrabold leading-none text-neutral-900 dark:text-white">
                            {fecha?.day ?? '—'}
                          </span>
                        </div>
                        <div className="min-w-0 flex-1">
                          <p className="truncate text-sm font-bold text-neutral-900 dark:text-white">
                            {item.titulo}
                          </p>
                          <p className="truncate text-xs font-medium text-neutral-500 dark:text-neutral-400">
                            {item.curso}
                          </p>
                        </div>
                        {item.estado === 'borrador' && (
                          <span className={badge.warning}>Borrador</span>
                        )}
                      </div>
                    )
                  })}
                </div>
              ) : (
                <div className="rounded-md border border-dashed border-neutral-200 p-6 text-center text-sm font-medium text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">
                  ¡Todo entregado! No tienes tareas pendientes.
                </div>
              )}
            </div>

            {/* Avisos */}
            <div className={`${card}`}>
              <div className="mb-6 flex items-center justify-between">
                <h2 className="text-base font-bold text-neutral-900 dark:text-white">
                  Avisos
                </h2>
                <Link
                  to="/mis-cursos-alumno"
                  data-twe-ripple-init
                  className="text-xs font-bold text-primary transition hover:text-primary-accent-300"
                >
                  Mis Cursos
                </Link>
              </div>

              {avisos.length > 0 ? (
                <div className="space-y-3">
                  {avisos.map((aviso) => (
                    <div
                      key={aviso.id_anuncio}
                      className="rounded-md border border-neutral-100 bg-neutral-50/50 p-4 transition-colors hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800/40 dark:hover:bg-neutral-700/50"
                    >
                      <div className="flex items-start gap-3">
                        <span className={`${badge.info} mt-0.5 shrink-0`}>NUEVO</span>
                        <div className="min-w-0 flex-1">
                          <p className="truncate text-sm font-bold text-neutral-900 dark:text-white">
                            {aviso.titulo}
                          </p>
                          <p className="mt-1 line-clamp-2 text-xs font-medium text-neutral-500 dark:text-neutral-400">
                            {aviso.contenido}
                          </p>
                          <p className="mt-2 text-xs font-semibold text-neutral-400 dark:text-neutral-500">
                            {aviso.curso} · {formatFecha(aviso.fecha_publicacion, { day: 'numeric', month: 'long' })}
                          </p>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="rounded-md border border-dashed border-neutral-200 p-6 text-center text-sm font-medium text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">
                  No hay avisos recientes de tus cursos.
                </div>
              )}
            </div>
          </div>
        </>
      )}

      {/* Vista director / secretaria */}
      {!isAlumno && !isAdmin && (
        <div className={`${card} flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between`}>
          <div>
            <h2 className="text-lg font-bold text-neutral-900 dark:text-white">Mi Resumen</h2>
            <p className="mt-1 text-sm font-medium text-neutral-500 dark:text-neutral-400">
              El resumen académico está disponible para el rol de estudiante.
            </p>
          </div>
          <div className="flex flex-wrap gap-3">
            <Link to="/reportes/actas" className={btn.primary}>Ir a Reportes</Link>
          </div>
        </div>
      )}

      {/* Optional Admin Extended View if logged in as Admin */}
      {isAdmin && (
        <div className="mt-12 pt-8 border-t border-neutral-300 space-y-8 dark:border-neutral-700">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-xl font-bold text-neutral-900 dark:text-white">
                Métricas de Infraestructura y Sistema
              </h2>
              <p className="text-xs text-neutral-500">Acceso rápido para administradores del SGA.</p>
            </div>
            <div className="flex gap-3">
              <Link to="/asignaciones/nuevo" className={btn.primary}>
                Asignar Curso
              </Link>
              <Link to="/admin/usuarios" className={btn.neutral}>
                Crear Usuario
              </Link>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
            <div className={`${card} text-center`}>
              <span className="text-xs font-medium text-neutral-500">Total Alumnos</span>
              <p className="mt-1 text-2xl font-bold text-primary">{stats.metrics?.alumnos || 0}</p>
            </div>
            <div className={`${card} text-center`}>
              <span className="text-xs font-medium text-neutral-500">Catedráticos</span>
              <p className="mt-1 text-2xl font-bold text-primary">{stats.metrics?.catedraticos || 0}</p>
            </div>
            <div className={`${card} text-center`}>
              <span className="text-xs font-medium text-neutral-500">Cursos Activos</span>
              <p className="mt-1 text-2xl font-bold text-primary">{stats.metrics?.cursos || 0}</p>
            </div>
            <div className={`${card} text-center`}>
              <span className="text-xs font-medium text-neutral-500">Inscripciones</span>
              <p className="mt-1 text-2xl font-bold text-primary">{stats.metrics?.inscripciones || 0}</p>
            </div>
          </div>

          {/* Gráfico */}
          {stats.charts?.alumnosPorCarrera?.length > 0 && (
            <div className={`${card} h-72`}>
              <p className="mb-4 text-sm font-bold text-neutral-700 dark:text-neutral-200">
                Alumnos por Carrera
              </p>
              <ResponsiveContainer width="100%" height="85%">
                <BarChart data={stats.charts.alumnosPorCarrera}>
                  <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e5e7eb" />
                  <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{fill: '#6b7280', fontSize: 11}} />
                  <YAxis axisLine={false} tickLine={false} tick={{fill: '#6b7280', fontSize: 11}} />
                  <Tooltip contentStyle={{borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)'}} />
                  <Bar dataKey="value" fill="#1266f1" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          )}
        </div>
      )}
    </div>
  )
}
