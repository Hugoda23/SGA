import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../../api/axios'

export default function ConfiguracionCursoIndex() {
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const navigate = useNavigate()

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
        <h1 className="mb-2 text-3xl font-bold text-neutral-800 dark:text-neutral-100">Configuración de Curso</h1>
        <p className="text-base font-medium text-neutral-500 dark:text-neutral-400">Selecciona un curso para configurar el avance programático y tareas.</p>
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

      {!loading && !error && data?.asignaciones?.length > 0 && (
        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
          {data.asignaciones.map((asignacion) => (
            <div 
              key={asignacion.id_asignacion} 
              onClick={() => navigate(`/configuracion-curso/${asignacion.id_asignacion}`)}
              className="group flex cursor-pointer flex-col overflow-hidden rounded-xl bg-white shadow-4 transition-all duration-300 hover:-translate-y-1 dark:bg-surface-dark"
            >
              <div className="border-b border-primary-100/50 bg-primary-50 p-6 dark:border-primary-900/50 dark:bg-primary-900/20">
                <div className="mb-4 flex items-start justify-between">
                  <span className="rounded-full bg-white px-3 py-1 text-xs font-bold text-primary shadow-sm">
                    {asignacion.codigo_curso}
                  </span>
                  <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-primary transition-all group-hover:bg-primary group-hover:text-white">
                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                  </div>
                </div>
                <h3 className="text-xl font-bold leading-tight text-neutral-800 transition-colors group-hover:text-primary dark:text-neutral-100">{asignacion.nombre_curso}</h3>
              </div>
              <div className="flex flex-1 flex-col justify-center p-6">
                <div className="flex items-center gap-2 text-neutral-500 dark:text-neutral-400">
                  <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                  <span className="text-sm font-medium">{asignacion.grado} - Sec {asignacion.seccion}</span>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
