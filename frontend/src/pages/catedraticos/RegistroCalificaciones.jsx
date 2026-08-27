import { useState, useEffect, useCallback, Fragment } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import api from '../../api/axios'
import { btn, table as tbl, card } from '../../lib/twClasses'
import Modal from '../../components/Modal'

export default function RegistroCalificaciones() {
  const { id_asignacion } = useParams()
  const navigate = useNavigate()

  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [saving, setSaving] = useState(false)
  const [notasTemp, setNotasTemp] = useState({})
  const [alertMessage, setAlertMessage] = useState(null)

  const fetchData = useCallback(async () => {
    try {
      setLoading(true)
      const res = await api.get(`/v1/registro-calificaciones/${id_asignacion}`)
      setData(res.data)

      // Inicializar el estado de edición temporal
      const temp = {}
      res.data.alumnos.forEach(alumno => {
        temp[alumno.id_inscripcion] = {}
        res.data.evaluaciones.forEach(ev => {
          temp[alumno.id_inscripcion][ev.id_evaluacion] = alumno.notas[ev.id_evaluacion]?.nota ?? ''
        })
      })
      setNotasTemp(temp)
    } catch (err) {
      console.error(err)
      setError('No se pudo cargar el registro de calificaciones.')
    } finally {
      setLoading(false)
    }
  }, [id_asignacion])

  useEffect(() => {
    fetchData()
  }, [fetchData])

  const handleNotaChange = (id_inscripcion, id_evaluacion, value) => {
    setNotasTemp(prev => ({
      ...prev,
      [id_inscripcion]: {
        ...prev[id_inscripcion],
        [id_evaluacion]: value
      }
    }))
  }

  const handleExportarPDF = async () => {
    try {
      const response = await api.get(`/v1/reportes/pdf/acta/${id_asignacion}`, { responseType: 'blob' })
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `acta_asignacion_${id_asignacion}.pdf`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch (err) {
      setAlertMessage('No se pudo exportar el acta a PDF.')
    }
  }

  const handleGuardar = async () => {
    try {
      setSaving(true)
      const payload = []
      Object.keys(notasTemp).forEach(id_ins => {
        Object.keys(notasTemp[id_ins]).forEach(id_ev => {
          const val = notasTemp[id_ins][id_ev]
          if (val !== '' && val !== null) {
            payload.push({
              id_inscripcion: parseInt(id_ins),
              id_evaluacion: parseInt(id_ev),
              nota: parseFloat(val)
            })
          }
        })
      })

      await api.post(`/v1/registro-calificaciones/${id_asignacion}/guardar`, { notas: payload })
      fetchData()
      setAlertMessage('Calificaciones guardadas correctamente')
    } catch (err) {
      console.error(err)
      setAlertMessage('Error al guardar calificaciones')
    } finally {
      setSaving(false)
    }
  }

  if (loading) return <div className="mx-auto max-w-7xl pb-12">
  <div className="flex flex-col items-center gap-3 py-16 text-neutral-500">
    <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
    <span className="text-lg font-semibold">Cargando...</span>
  </div>
</div>
  if (error) return <div className="mx-auto max-w-7xl px-4 pb-12">
  <div className="flex items-center gap-3 rounded-xl border border-danger-100 bg-danger-50 p-6 text-danger">
    <svg className="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
    <span className="font-medium">{error}</span>
  </div>
</div>
  if (!data) return null

  const { asignacion, zonas = [], evaluaciones_sin_zona = [], alumnos } = data

  const grupos = [
    ...zonas.map(z => ({
      key: `zona-${z.id_zona}`,
      nombre: z.nombre,
      puntos: parseFloat(z.puntos) || 0,
      actividades: [
        ...(z.evaluaciones || []).map(ev => ({ ...ev, tipo: 'evaluacion', key: `ev-${ev.id_evaluacion}` })),
        ...(z.tareas || []).map(t => ({ ...t, tipo: 'tarea', key: `t-${t.id_tarea}`, porcentaje: t.puntos })),
      ],
      esZona: true,
    })),
  ]
  if (evaluaciones_sin_zona.length > 0) {
    grupos.push({
      key: 'sin-zona',
      nombre: 'Sin zona',
      puntos: evaluaciones_sin_zona.reduce((acc, ev) => acc + (parseFloat(ev.porcentaje) || 0), 0),
      actividades: evaluaciones_sin_zona.map(ev => ({ ...ev, tipo: 'evaluacion', key: `ev-${ev.id_evaluacion}` })),
      esZona: false,
    })
  }

  const numeroColumnas = 1 + grupos.reduce((acc, g) => acc + g.actividades.length + 1, 0) + 1

  const totalZona = (alumno, grupo) =>
    grupo.actividades.reduce((acc, act) => {
      const val = act.tipo === 'tarea'
        ? parseFloat(alumno.notas_tareas?.[act.id_tarea])
        : parseFloat(notasTemp[alumno.id_inscripcion]?.[act.id_evaluacion])
      return acc + (isNaN(val) ? 0 : val)
    }, 0)

  return (
    <div className="mx-auto max-w-7xl pb-12">
      {/* Back button */}
      <div className="mb-6">
        <button
          onClick={() => navigate('/mis-cursos')}
          className="flex items-center gap-1 text-sm font-semibold text-primary hover:text-primary-accent-300"
        >
          &larr; Volver a Mis Cursos
        </button>
      </div>

      {/* Header Section */}
      <div className={`${card} mb-8 flex flex-col items-start justify-between gap-6 md:flex-row md:items-center`}>
        <div>
          <div className="mb-3 inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-bold text-primary dark:bg-primary-900/30 dark:text-primary-300">
            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
            {asignacion.grado} - {asignacion.seccion}
          </div>
          <h1 className="text-3xl font-bold text-neutral-800 dark:text-neutral-100">
            {asignacion.curso}
          </h1>
        </div>

        <div className="flex rounded-xl bg-neutral-100 p-1 dark:bg-neutral-700">
          <button onClick={() => navigate(`/asistencia/${id_asignacion}`)} className="rounded-lg px-6 py-2.5 text-sm font-bold text-neutral-500 transition-colors hover:bg-white/60 hover:text-neutral-900 dark:text-neutral-300 dark:hover:bg-neutral-800">
            Control de Asistencia
          </button>
          <button className="rounded-lg bg-white px-8 py-2.5 text-sm font-bold text-primary shadow-sm dark:bg-neutral-800 dark:text-primary-300">
            Calificaciones
          </button>
        </div>
      </div>

      {/* Actions */}
      <div className="mb-6 flex justify-end gap-4">
        <button
          onClick={handleGuardar}
          disabled={saving}
          className={`${btn.primary} disabled:opacity-50`}
        >
          {saving ? 'Guardando...' : (
            <><svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg> Guardar Cambios</>
          )}
        </button>
        <button onClick={handleExportarPDF} className={btn.outline}>
          <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg> Exportar a PDF
        </button>
      </div>

      {/* Table Section */}
      <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
        <div className="overflow-x-auto">
          {grupos.length === 0 ? (
            <div className="p-12 text-center text-neutral-500 dark:text-neutral-400">
              Aún no hay actividades de evaluación. Configúralas desde la pestaña
              &quot;Evaluaciones&quot; en Configuración del Curso.
            </div>
          ) : (
            <table className="w-full text-sm">
              <thead className={tbl.head}>
                <tr>
                  <th className={tbl.th}>ALUMNO</th>
                  {grupos.map(g => (
                    <th key={g.key} colSpan={g.actividades.length + 1} className={`${tbl.th} text-center`}>
                      <span className="inline-flex items-center gap-2 rounded-full bg-primary px-3 py-1 text-xs font-bold text-white">
                        {g.nombre} · {g.puntos} pts
                      </span>
                    </th>
                  ))}
                  <th className={`${tbl.th} bg-primary-50/60 text-center`}>
                    <div className="font-bold text-primary-800 dark:text-primary-300">NOTA FINAL</div>
                    <div className="mt-1 text-xs font-semibold text-primary-600/70">/ 100</div>
                  </th>
                </tr>
                <tr>
                  <th className={`${tbl.th} font-normal text-neutral-400`}></th>
                  {grupos.map(g => (
                    <Fragment key={g.key}>
                      {g.actividades.map(act => (
                        <th key={act.key} className={`${tbl.th} text-center font-normal`}>
                          <div className="font-semibold text-neutral-700 dark:text-neutral-200">{act.nombre}</div>
                          <div className="mt-1 text-xs text-primary">{act.porcentaje} pts</div>
                          {act.tipo === 'tarea' && (
                            <div className="mt-0.5 text-[10px] font-bold uppercase tracking-wide text-neutral-400">Tarea (se califica en Entregas)</div>
                          )}
                        </th>
                      ))}
                      <th className={`${tbl.th} bg-primary-50/40 text-center font-normal text-primary`}>Total</th>
                    </Fragment>
                  ))}
                  <th className={`${tbl.th} bg-primary-50/60`}></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-neutral-100 dark:divide-neutral-700">
                {alumnos.map(alumno => (
                  <tr key={alumno.id_inscripcion} className={tbl.row}>
                    <td className={`${tbl.td} font-bold`}>
                      <div className="flex items-center gap-3">
                        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary">
                          {alumno.alumno.nombre.charAt(0)}{alumno.alumno.apellido?.charAt(0)}
                        </div>
                        {alumno.alumno.nombre} {alumno.alumno.apellido}
                      </div>
                    </td>
                    {grupos.map(g => (
                      <Fragment key={g.key}>
                        {g.actividades.map(act => (
                          <td key={act.key} className="p-3 text-center">
                            {act.tipo === 'tarea' ? (
                              <span className="inline-flex w-16 items-center justify-center rounded-lg bg-neutral-100 py-1.5 font-bold text-neutral-700 dark:bg-neutral-700/50 dark:text-neutral-200">
                                {alumno.notas_tareas?.[act.id_tarea] ?? '—'}
                              </span>
                            ) : (
                              <input
                                type="number"
                                min="0"
                                max={act.porcentaje}
                                step="0.5"
                                className="w-16 rounded-lg border border-transparent bg-neutral-100 py-1.5 text-center font-bold text-neutral-700 outline-none transition-all focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary dark:bg-neutral-700/50 dark:text-neutral-200 dark:focus:bg-neutral-800"
                                value={notasTemp[alumno.id_inscripcion]?.[act.id_evaluacion] ?? ''}
                                onChange={(e) => handleNotaChange(alumno.id_inscripcion, act.id_evaluacion, e.target.value)}
                              />
                            )}
                          </td>
                        ))}
                        <td className={`bg-primary-50/40 p-3 text-center font-bold text-primary ${g.esZona ? '' : 'opacity-60'}`}>
                          {totalZona(alumno, g).toFixed(1)}
                        </td>
                      </Fragment>
                    ))}
                    <td className="bg-primary-50/60 p-4 text-center font-extrabold text-primary-800 dark:bg-primary-900/20 dark:text-primary-300">
                      {alumno.nota_final ?? '-'}
                    </td>
                  </tr>
                ))}
                {alumnos.length === 0 && (
                  <tr>
                    <td colSpan={numeroColumnas} className="p-12 text-center text-neutral-500">
                      No hay alumnos inscritos en este curso.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          )}
        </div>
      </div>

      {/* Alert Modal */}
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
