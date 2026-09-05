import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../../api/axios'
import { btn, badge } from '../../lib/twClasses'
import PdfViewerModal from '../../components/PdfViewerModal'
import usePdfViewer from '../../hooks/usePdfViewer'

function formatHorario(proximo) {
  if (!proximo) return 'Sin horario'
  const { dia, hora } = proximo
  if (!dia && !hora) return 'Sin horario'
  if (dia && hora) return `${capitalize(dia)}, ${hora}`
  return hora || capitalize(dia)
}

function capitalize(str) {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase()
}

function CursoCard({ asignacion }) {
  const navigate = useNavigate()
  const { pdf, abrirPdf, cerrarPdf, cargando } = usePdfViewer()
  const proximaLabel = formatHorario(asignacion.proximo_horario)
  const tareasBadge = asignacion.tareas_pendientes > 0
    ? { label: `${asignacion.tareas_pendientes} pendientes`, color: badge.warning }
    : { label: 'Al día', color: badge.success }

  const handleListadoPDF = async () => {
    try {
      await abrirPdf(`/v1/reportes/pdf/listado-alumnos/${asignacion.id_asignacion}`, {
        clave: 'listado',
        nombreArchivo: `listado_alumnos_${asignacion.id_asignacion}.pdf`,
        titulo: `Listado de alumnos — ${asignacion.nombre_curso || `ASG-${asignacion.id_asignacion}`}`,
      })
    } catch (err) {
      console.error(err)
    }
  }

  return (
    <div className="group flex flex-col overflow-hidden rounded-xl bg-white shadow-4 transition-all duration-300 hover:-translate-y-1 dark:bg-surface-dark">
      <div className="border-b border-primary-100/50 bg-primary-50 p-6 dark:border-primary-900/50 dark:bg-primary-900/20">
        <div className="mb-4 flex items-start justify-between">
          <span className={badge.primary}>
            {asignacion.codigo_curso}
          </span>
          <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-primary transition-transform group-hover:scale-110">
            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
          </div>
        </div>
        <h3 className="text-xl font-bold leading-tight text-neutral-800 transition-colors group-hover:text-primary dark:text-neutral-100">{asignacion.nombre_curso}</h3>
      </div>
      
      <div className="flex flex-1 flex-col p-6">
        <div className="mb-6 flex items-center gap-2 text-neutral-500 dark:text-neutral-400">
          <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
          <span className="text-sm font-medium">{asignacion.grado} - Sec {asignacion.seccion}</span>
        </div>

        <div className="mb-6 space-y-3">
          <div className="flex items-center justify-between text-sm">
            <span className="flex items-center gap-2 text-neutral-500 dark:text-neutral-400">
              <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              Próxima clase
            </span>
            <span className="font-semibold text-neutral-700 dark:text-neutral-200">{proximaLabel}</span>
          </div>
          <div className="flex items-center justify-between text-sm">
            <span className="flex items-center gap-2 text-neutral-500 dark:text-neutral-400">
              <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
              Tareas
            </span>
            <span className={tareasBadge.color}>
              {tareasBadge.label}
            </span>
          </div>
        </div>

        <div className="mt-auto grid grid-cols-2 gap-3">
          <button 
            onClick={() => navigate(`/registro-calificaciones/${asignacion.id_asignacion}`)}
            className={`${btn.primary} w-full`}
          >
            Notas
          </button>
          <button 
            onClick={() => navigate(`/asistencia/${asignacion.id_asignacion}`)}
            className={`${btn.success} w-full`}
          >
            Asistencia
          </button>
          <button
            onClick={handleListadoPDF}
            disabled={cargando === 'listado'}
            className={`${btn.outline} w-full disabled:cursor-not-allowed disabled:opacity-60`}
          >
            {cargando === 'listado' ? 'Generando...' : 'Ver Listado'}
          </button>
          <button
            onClick={() => navigate(`/configuracion-curso/${asignacion.id_asignacion}`)}
            className={`${btn.neutral} w-full`}
          >
            Configuración
          </button>
        </div>
      </div>

      <PdfViewerModal
        open={!!pdf}
        onClose={cerrarPdf}
        url={pdf?.url}
        nombreArchivo={pdf?.nombreArchivo}
        titulo={pdf?.titulo}
      />
    </div>
  )
}

export default function MisCursos() {
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    api
      .get('/v1/catedratico/mis-cursos')
      .then((res) => setData(res.data))
      .catch((err) => {
        console.error(err)
        setError('No se pudieron cargar los cursos.')
      })
      .finally(() => setLoading(false))
  }, [])

  return (
    <div className="mx-auto max-w-7xl pb-12">
      <div className="mb-8">
        <h1 className="mb-2 text-3xl font-bold text-neutral-800 dark:text-neutral-100">Mis Cursos</h1>
        <p className="text-base font-medium text-neutral-500 dark:text-neutral-400">Gestiona tus asignaturas y accede a los módulos de control.</p>
      </div>

      {loading && (
        <div className="flex flex-col items-center justify-center gap-4 rounded-xl bg-white/50 py-20 shadow-sm text-primary dark:bg-surface-dark/50">
          <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
          <span className="text-lg font-semibold">Cargando tus cursos...</span>
        </div>
      )}

      {!loading && error && (
        <div className="flex items-center justify-center gap-3 rounded-xl border border-danger-100 bg-danger-50 p-10 text-danger shadow-sm">
          <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          <span className="font-medium">{error}</span>
        </div>
      )}

      {!loading && !error && data?.asignaciones?.length === 0 && (
        <div className="flex flex-col items-center justify-center gap-4 rounded-xl border border-neutral-200 bg-white py-16 font-medium text-neutral-500 dark:border-neutral-700 dark:bg-surface-dark">
          <svg className="mb-4 h-12 w-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
          No tienes cursos asignados.
        </div>
      )}

      {!loading && !error && data?.asignaciones?.length > 0 && (
        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
          {data.asignaciones.map((a) => (
            <CursoCard key={a.id_asignacion} asignacion={a} />
          ))}
        </div>
      )}
    </div>
  )
}
