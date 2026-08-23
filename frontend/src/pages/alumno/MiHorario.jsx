import { useState, useEffect, useCallback } from 'react'
import { useAuth } from '../../context/AuthContext'
import api from '../../api/axios'
import { badge } from '../../lib/twClasses'

const DIAS = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']
const COLORES = ['bg-primary-50 border-primary-200 text-primary dark:bg-primary-900/30 dark:border-primary-800',
  'bg-secondary-50 border-secondary-200 text-secondary dark:bg-secondary-900/30 dark:border-secondary-800',
  'bg-success-50 border-success-200 text-success dark:bg-success-900/30 dark:border-success-800',
  'bg-warning-50 border-warning-200 text-warning dark:bg-warning-900/30 dark:border-warning-800',
  'bg-danger-50 border-danger-200 text-danger dark:bg-danger-900/30 dark:border-danger-800']

export default function MiHorario() {
  const { user } = useAuth()
  const [clases, setClases] = useState([])
  const [loading, setLoading] = useState(true)

  const isAlumno = user?.roles?.some((r) => r.nombre === 'alumno')

  const fetchHorario = useCallback(() => {
    if (!isAlumno) { setLoading(false); return }
    setLoading(true)
    api.get('/v1/alumno/horario')
      .then((r) => setClases(r.data))
      .catch(console.error)
      .finally(() => setLoading(false))
  }, [isAlumno])

  useEffect(() => { fetchHorario() }, [fetchHorario])

  const clasesPorDia = (dia) =>
    clases
      .filter((c) => c.dia_semana === dia)
      .sort((a, b) => (a.hora_inicio < b.hora_inicio ? -1 : 1))

  const colorDe = (dia, idx) => COLORES[(DIAS.indexOf(dia) + idx) % COLORES.length]

  if (!isAlumno) {
    return (
      <div className="mx-auto max-w-5xl pb-12">
        <h1 className="mb-6 text-3xl font-bold text-neutral-800 dark:text-neutral-100">Mi Horario</h1>
        <div className="rounded-xl border border-warning-200 bg-warning-50 p-6 text-sm font-medium text-warning dark:bg-warning-900/30 dark:text-warning-300">
          Esta sección es solo para alumnos.
        </div>
      </div>
    )
  }

  return (
    <div className="mx-auto max-w-6xl pb-12">
      <h1 className="text-3xl font-bold text-neutral-800 dark:text-neutral-100">Mi Horario</h1>
      <p className="mb-8 font-medium text-neutral-500 dark:text-neutral-400">
        Horario de clases de tus cursos activos.
      </p>

      {loading && (
        <div className="flex flex-col items-center gap-3 py-16 text-neutral-500">
          <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary-100 border-t-primary"></div>
          <span className="text-lg font-semibold">Cargando...</span>
        </div>
      )}

      {!loading && clases.length === 0 && (
        <div className="rounded-xl bg-white p-12 text-center shadow-4 dark:bg-surface-dark">
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary-50 text-primary dark:bg-primary-900/30">
            <svg className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
          <p className="text-lg font-semibold text-neutral-600 dark:text-neutral-300">Sin horario registrado</p>
          <p className="mt-1 text-sm text-neutral-400 dark:text-neutral-500">Tus cursos aún no tienen horarios asignados.</p>
        </div>
      )}

      {!loading && clases.length > 0 && (
        <div className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
          {DIAS.map((dia) => {
            const deDia = clasesPorDia(dia)
            if (deDia.length === 0) return null
            return (
              <div key={dia} className="rounded-xl bg-white p-5 shadow-4 dark:bg-surface-dark">
                <h3 className="mb-3 flex items-center justify-between border-b border-neutral-100 pb-2 text-lg font-bold text-neutral-800 dark:border-neutral-800 dark:text-neutral-100">
                  {dia}
                  <span className={badge.neutral}>{deDia.length} clase{deDia.length !== 1 ? 's' : ''}</span>
                </h3>
                <ul className="space-y-3">
                  {deDia.map((c, i) => (
                    <li key={`${dia}-${c.curso}-${c.hora_inicio}`} className={`rounded-lg border px-4 py-3 ${colorDe(dia, i)}`}>
                      <p className="text-sm font-bold leading-tight">{c.curso}</p>
                      <p className="mt-0.5 text-xs opacity-80">{c.codigo_curso} · {c.grado} &quot;{c.seccion}&quot;</p>
                      <div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-medium opacity-90">
                        <span>{c.hora_inicio} – {c.hora_fin}</span>
                        <span className="inline-flex items-center gap-1">
                          <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
                          {c.aula}
                        </span>
                        <span className="text-xs opacity-70">Periodo: {c.periodo}</span>
                      </div>
                    </li>
                  ))}
                </ul>
              </div>
            )
          })}
        </div>
      )}
    </div>
  )
}
