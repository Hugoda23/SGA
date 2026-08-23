import { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'
import api from '../../api/axios'
import { badge } from '../../lib/twClasses'

export default function MisCursosAlumno() {
  const { user } = useAuth()
  const navigate = useNavigate()
  const [cursos, setCursos] = useState([])
  const [loading, setLoading] = useState(true)

  const isAlumno = user?.roles?.some((r) => r.nombre === 'alumno')

  const fetchCursos = useCallback(() => {
    if (!isAlumno) { setLoading(false); return }
    setLoading(true)
    api.get('/v1/alumno/mis-cursos')
      .then((r) => setCursos(r.data))
      .catch(console.error)
      .finally(() => setLoading(false))
  }, [isAlumno])

  useEffect(() => { fetchCursos() }, [fetchCursos])

  if (!isAlumno) {
    return (
      <div className="mx-auto max-w-5xl pb-12">
        <h1 className="mb-6 text-3xl font-bold text-neutral-800 dark:text-neutral-100">Mis Cursos</h1>
        <div className="rounded-xl border border-warning-200 bg-warning-50 p-6 text-sm font-medium text-warning dark:bg-warning-900/30 dark:text-warning-300">
          Esta sección es solo para alumnos.
        </div>
      </div>
    )
  }

  return (
    <div className="mx-auto max-w-5xl pb-12">
      <h1 className="text-3xl font-bold text-neutral-800 dark:text-neutral-100">Mis Cursos</h1>
      <p className="mb-8 font-medium text-neutral-500 dark:text-neutral-400">Cursos en los que estás inscrito este período.</p>

      {loading && (
        <div className="flex flex-col items-center gap-3 py-16 text-neutral-500">
          <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
          <span className="text-lg font-semibold">Cargando...</span>
        </div>
      )}

      {!loading && cursos.length === 0 && (
        <div className="rounded-xl bg-white p-12 text-center shadow-4 dark:bg-surface-dark">
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary-50 text-primary dark:bg-primary-900/30">
            <svg className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
          </div>
          <p className="text-lg font-semibold text-neutral-600 dark:text-neutral-300">No estás inscrito en ningún curso</p>
          <p className="mt-1 text-sm text-neutral-400 dark:text-neutral-500">Cuando la secretaría te inscriba aparecerán aquí.</p>
        </div>
      )}

      {!loading && cursos.length > 0 && (
        <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
          {cursos.map((c) => (
            <button
              key={c.id_asignacion}
              onClick={() => navigate(`/mis-cursos-alumno/${c.id_asignacion}`)}
              className="group flex flex-col rounded-xl bg-white p-6 text-left shadow-4 transition-all hover:-translate-y-0.5 hover:shadow-lg dark:bg-surface-dark"
            >
              <div className="flex items-start justify-between gap-3">
                <div>
                  <p className="text-xs font-bold uppercase tracking-wide text-primary">{c.codigo_curso}</p>
                  <h3 className="mt-1 text-lg font-bold text-neutral-800 dark:text-neutral-100">{c.curso}</h3>
                  <p className="mt-0.5 text-sm text-neutral-500 dark:text-neutral-400">
                    {c.grado} &quot;{c.seccion}&quot; · {c.periodo}
                  </p>
                </div>
                {c.periodo_estado === 'activo' ? (
                  <span className={badge.success}>Activo</span>
                ) : (
                  <span className={badge.neutral}>{c.periodo_estado}</span>
                )}
              </div>

              <p className="mt-3 text-xs text-neutral-400 dark:text-neutral-500">Catedrático: {c.catedratico}</p>

              <div className="mt-4 grid grid-cols-4 gap-2 border-t border-neutral-100 pt-4 text-center dark:border-neutral-600">
                <div>
                  <p className="text-lg font-bold text-neutral-800 dark:text-neutral-100">{c.total_unidades}</p>
                  <p className="text-[10px] font-semibold uppercase text-neutral-400">Semanas</p>
                </div>
                <div>
                  <p className="text-lg font-bold text-neutral-800 dark:text-neutral-100">{c.total_tareas}</p>
                  <p className="text-[10px] font-semibold uppercase text-neutral-400">Tareas</p>
                </div>
                <div>
                  <p className="text-lg font-bold text-neutral-800 dark:text-neutral-100">{c.total_materiales}</p>
                  <p className="text-[10px] font-semibold uppercase text-neutral-400">Materiales</p>
                </div>
                <div>
                  <p className="text-lg font-bold text-neutral-800 dark:text-neutral-100">{c.total_anuncios}</p>
                  <p className="text-[10px] font-semibold uppercase text-neutral-400">Anuncios</p>
                </div>
              </div>

              <div className="mt-4 flex items-center justify-between">
                <span className="text-xs text-neutral-400 dark:text-neutral-500">{c.tareas_pendientes} tarea(s) pendiente(s)</span>
                <span className="text-sm font-bold text-primary transition-transform group-hover:translate-x-1">Ver curso →</span>
              </div>
            </button>
          ))}
        </div>
      )}
    </div>
  )
}
