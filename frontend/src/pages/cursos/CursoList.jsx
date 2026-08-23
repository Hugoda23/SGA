import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../../api/axios'
import { btn, badge } from '../../lib/twClasses'
import Modal from '../../components/Modal'

export default function CursoList() {
  const [data, setData] = useState([])
  const [loading, setLoading] = useState(true)
  const [alertMessage, setAlertMessage] = useState(null)
  const [confirmAction, setConfirmAction] = useState(null)
  const navigate = useNavigate()

  const fetchData = async () => {
    try {
      const res = await api.get('/v1/cursos')
      setData(res.data)
    } catch (err) {
      console.error(err)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { fetchData() }, [])

  const handleDelete = (row) => {
    setConfirmAction({
      message: `¿Estás seguro de que deseas eliminar permanentemente el curso "${row.nombre_curso}"?`,
      onConfirm: async () => {
        try { 
          await api.delete(`/v1/cursos/${row.id_curso}`); 
          fetchData(); 
          setAlertMessage('Curso eliminado correctamente.');
        } catch (err) { 
          setAlertMessage(err.response?.data?.message || 'Error al eliminar');
        }
      }
    });
  }

  return (
    <div className="mx-auto max-w-7xl pb-12">
      <div className="mb-8 flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
        <div>
          <h1 className="mb-2 text-3xl font-bold text-neutral-800 dark:text-neutral-100">Catálogo de Cursos</h1>
          <p className="text-base font-medium text-neutral-500 dark:text-neutral-400">Gestión de oferta académica y asignación docente.</p>
        </div>
        <button
          type="button"
          onClick={() => navigate('/cursos/nuevo')}
          className={btn.primary}
        >
          <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
          Crear Curso
        </button>
      </div>

      {loading ? (
        <div className="flex flex-col items-center gap-3 py-16 text-neutral-500">
          <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
          <span className="font-semibold">Cargando cursos...</span>
        </div>
      ) : data.length === 0 ? (
        <div className="rounded-xl border border-neutral-100 bg-white py-16 text-center text-neutral-500 shadow-4 dark:bg-surface-dark">
          <svg className="mb-4 h-12 w-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
          <span className="text-lg font-semibold text-neutral-600 dark:text-neutral-300">No hay cursos registrados</span>
          <p className="text-sm text-neutral-400">Crea un nuevo curso para comenzar.</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
          {data.map(curso => (
            <div key={curso.id_curso} className="flex flex-col overflow-hidden rounded-xl border border-neutral-100 bg-white shadow-4 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg dark:bg-surface-dark dark:border-neutral-700">
              
              <div className="flex-1 p-5">
                <div className="mb-4 flex items-center justify-between">
                  <span className={`${badge.info} uppercase`}>
                    CUR-{String(curso.id_curso).padStart(3, '0')}
                  </span>
                  <span className="flex items-center gap-2">
                    {curso.creditos != null && (
                      <span className={badge.neutral}>
                        {curso.creditos} crédito{curso.creditos !== 1 ? 's' : ''}
                      </span>
                    )}
                    <span className="text-xs font-bold uppercase tracking-wider text-neutral-400">
                      {curso.carreras?.length
                        ? curso.carreras.map((c) => c.nombre_carrera).join(', ')
                        : 'BÁSICO'}
                    </span>
                  </span>
                </div>
                <h3 className="text-lg font-bold leading-tight text-neutral-900 dark:text-neutral-100">
                  {curso.nombre_curso}
                </h3>
              </div>

              <div className="space-y-3 border-t border-neutral-100 bg-neutral-50/50 px-5 py-4 text-sm dark:border-neutral-700 dark:bg-neutral-800/40">
                <div className="flex items-start text-neutral-700 dark:text-neutral-300">
                  <span className="mr-2 font-bold text-neutral-400">
                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                  </span> 
                  <span className="mr-1 font-medium">Docente:</span> 
                  <span className="line-clamp-1 text-neutral-600 dark:text-neutral-300">
                    {curso.asignaciones?.length > 0 
                      ? curso.asignaciones.map(a => a.catedratico ? `${a.catedratico.nombre} ${a.catedratico.apellido}` : '').filter(Boolean).join(', ') || 'Pendiente asignación'
                      : 'Pendiente asignación'}
                  </span>
                </div>
                <div className="flex items-start text-neutral-700 dark:text-neutral-300">
                  <span className="mr-2 font-bold text-neutral-400">
                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                  </span> 
                  <span className="mr-1 font-medium">Estudiantes:</span> 
                  <span className="text-neutral-600 dark:text-neutral-300">{curso.asignaciones?.reduce((acc, asig) => acc + (asig.inscripciones?.length || 0), 0) || 0} inscritos</span>
                </div>
              </div>

              <div className="border-t border-neutral-100 bg-white p-5 dark:border-neutral-700 dark:bg-surface-dark">
                <div className="flex flex-col gap-2">
                  <button
                    type="button"
                    onClick={() => navigate(`/asignaciones/nuevo?curso_id=${curso.id_curso}`)}
                    className={`${btn.primary} w-full`}
                  >
                    + Asignar Docente
                  </button>
                  <div className="flex gap-2">
                    <button
                      type="button"
                      onClick={() => navigate(`/cursos/${curso.id_curso}`)}
                      className={`${btn.outline} flex-1`}
                    >
                      Gestionar Curso
                    </button>
                    <button
                      type="button"
                      onClick={() => handleDelete(curso)}
                      className="rounded-lg bg-danger-50 px-4 py-2.5 font-bold text-danger transition-colors hover:bg-danger hover:text-white dark:bg-danger-100/10"
                      title="Eliminar curso"
                    >
                      <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      <Modal
        open={!!alertMessage}
        onClose={() => setAlertMessage(null)}
        title="Mensaje del Sistema"
        size="sm"
        footer={
          <button type="button" onClick={() => setAlertMessage(null)} className={btn.primary}>
            Aceptar
          </button>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">{alertMessage}</p>
      </Modal>

      {confirmAction && (
      <Modal
        open
        onClose={() => setConfirmAction(null)}
        title="Confirmación"
        size="sm"
        footer={
          <>
            <button type="button" onClick={() => setConfirmAction(null)} className={btn.ghost}>Cancelar</button>
            <button
              type="button"
              onClick={() => { confirmAction.onConfirm(); setConfirmAction(null) }}
              className={btn.danger}
            >
              Sí, Eliminar
            </button>
          </>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">{confirmAction.message}</p>
      </Modal>
      )}
    </div>
  )
}
