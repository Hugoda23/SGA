import { useState, useEffect, useCallback } from 'react'
import { useAuth } from '../../context/AuthContext'
import api from '../../api/axios'
import { btn, input, table as tbl, badge } from '../../lib/twClasses'

export default function ReporteRendimiento() {
  const { user } = useAuth()
  const [periodos, setPeriodos] = useState([])
  const [grados, setGrados] = useState([])
  const [periodoId, setPeriodoId] = useState('')
  const [gradoId, setGradoId] = useState('')
  const [resultado, setResultado] = useState(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)

  const puedeVer = user?.roles?.some((r) => ['admin', 'director'].includes(r.nombre))

  useEffect(() => {
    api.get('/v1/periodos-academicos').then((r) => {
      const lista = Array.isArray(r.data) ? r.data : r.data.data ?? []
      setPeriodos(lista)
    }).catch(console.error)
    api.get('/v1/grados').then((r) => {
      const lista = Array.isArray(r.data) ? r.data : r.data.data ?? []
      setGrados(lista)
    }).catch(console.error)
  }, [])

  const cargar = useCallback(async () => {
    if (!periodoId) return
    setLoading(true)
    setError(null)
    try {
      const params = { periodo_id: periodoId }
      if (gradoId) params.grado_id = gradoId
      const r = await api.get('/v1/reportes/rendimiento', { params })
      setResultado(r.data)
    } catch {
      setError('No se pudo generar el reporte.')
    } finally {
      setLoading(false)
    }
  }, [periodoId, gradoId])

  if (!puedeVer) {
    return (
      <div className="mx-auto max-w-5xl pb-12">
        <h1 className="mb-6 text-3xl font-bold text-neutral-800 dark:text-neutral-100">Rendimiento Académico</h1>
        <div className="rounded-xl border border-warning-200 bg-warning-50 p-6 text-sm font-medium text-warning dark:bg-warning-900/30 dark:text-warning-300">
          No tienes permisos para ver este reporte.
        </div>
      </div>
    )
  }

  return (
    <div className="mx-auto max-w-6xl pb-12">
      <h1 className="text-3xl font-bold text-neutral-800 dark:text-neutral-100">Rendimiento Académico</h1>
      <p className="mb-8 font-medium text-neutral-500 dark:text-neutral-400">
        Aprueba, reprueba y promedio por asignación según el periodo académico.
      </p>

      <div className="mb-6 flex flex-col gap-4 rounded-xl bg-white p-5 shadow-4 dark:bg-surface-dark md:flex-row md:items-end">
        <div className="flex-1">
          <label className={input.label}>Periodo académico</label>
          <select
            value={periodoId}
            onChange={(e) => { setPeriodoId(e.target.value); setResultado(null) }}
            className={input.base}
          >
            <option value="">Selecciona un periodo</option>
            {periodos.map((p) => (
              <option key={p.id_periodo} value={p.id_periodo}>{p.nombre}</option>
            ))}
          </select>
        </div>
        <div className="flex-1">
          <label className={input.label}>Grado (opcional)</label>
          <select
            value={gradoId}
            onChange={(e) => setGradoId(e.target.value)}
            className={input.base}
          >
            <option value="">Todos los grados</option>
            {grados.map((g) => (
              <option key={g.id_grado} value={g.id_grado}>{g.nombre}</option>
            ))}
          </select>
        </div>
        <button type="button" onClick={cargar} disabled={!periodoId || loading} className={btn.primary}>
          {loading ? 'Generando...' : 'Generar reporte'}
        </button>
      </div>

      {error && (
        <div className="mb-6 rounded-xl border border-danger-200 bg-danger-50 p-4 text-sm font-medium text-danger dark:bg-danger-900/30 dark:text-danger-300">
          {error}
        </div>
      )}

      {resultado && (
        <>
          <div className="mb-6 grid grid-cols-2 gap-4 md:grid-cols-5">
            <div className="rounded-xl bg-white p-4 shadow-4 dark:bg-surface-dark">
              <p className="text-xs font-bold uppercase tracking-wide text-neutral-400">Asignaciones</p>
              <p className="mt-1 text-2xl font-bold text-primary">{resultado.resumen.asignaciones}</p>
            </div>
            <div className="rounded-xl bg-white p-4 shadow-4 dark:bg-surface-dark">
              <p className="text-xs font-bold uppercase tracking-wide text-neutral-400">Inscritos</p>
              <p className="mt-1 text-2xl font-bold text-neutral-800 dark:text-neutral-100">{resultado.resumen.inscritos}</p>
            </div>
            <div className="rounded-xl bg-white p-4 shadow-4 dark:bg-surface-dark">
              <p className="text-xs font-bold uppercase tracking-wide text-neutral-400">Aprobados</p>
              <p className="mt-1 text-2xl font-bold text-success">{resultado.resumen.aprobados}</p>
            </div>
            <div className="rounded-xl bg-white p-4 shadow-4 dark:bg-surface-dark">
              <p className="text-xs font-bold uppercase tracking-wide text-neutral-400">Reprobados</p>
              <p className="mt-1 text-2xl font-bold text-danger">{resultado.resumen.reprobados}</p>
            </div>
            <div className="rounded-xl bg-white p-4 shadow-4 dark:bg-surface-dark">
              <p className="text-xs font-bold uppercase tracking-wide text-neutral-400">Promedio general</p>
              <p className="mt-1 text-2xl font-bold text-neutral-800 dark:text-neutral-100">
                {resultado.resumen.promedio_general ?? '—'}
              </p>
            </div>
          </div>

          <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className={tbl.head}>
                  <tr>
                    <th className={tbl.th}>Curso</th>
                    <th className={tbl.th}>Grado / Sección</th>
                    <th className={tbl.th}>Catedrático</th>
                    <th className={tbl.th}>Inscritos</th>
                    <th className={tbl.th}>Con nota</th>
                    <th className={tbl.th}>Aprobados</th>
                    <th className={tbl.th}>Reprobados</th>
                    <th className={tbl.th}>% Aprobación</th>
                    <th className={tbl.th}>Promedio</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-neutral-100 dark:divide-neutral-700">
                  {resultado.asignaciones.map((a) => (
                    <tr key={a.id_asignacion} className={tbl.row}>
                      <td className={tbl.td}>{a.curso}</td>
                      <td className={tbl.td}>{a.grado} {a.seccion ? `"${a.seccion}"` : ''}</td>
                      <td className={tbl.td}>{a.catedratico}</td>
                      <td className={tbl.td}>{a.inscritos}</td>
                      <td className={tbl.td}>{a.con_nota}</td>
                      <td className={tbl.td}>
                        <span className={badge.success}>{a.aprobados}</span>
                      </td>
                      <td className={tbl.td}>
                        <span className={a.reprobados > 0 ? badge.danger : badge.neutral}>{a.reprobados}</span>
                      </td>
                      <td className={tbl.td}>{a.porcentaje_aprobacion !== null ? `${a.porcentaje_aprobacion}%` : '—'}</td>
                      <td className={tbl.td}>{a.promedio ?? '—'}</td>
                    </tr>
                  ))}
                  {resultado.asignaciones.length === 0 && (
                    <tr>
                      <td colSpan={9} className="px-4 py-10 text-center font-medium text-neutral-400">
                        No hay asignaciones para los filtros seleccionados.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </>
      )}
    </div>
  )
}
