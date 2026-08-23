import { useState, useEffect } from 'react'
import api from '../../api/axios'
import { table as tbl } from '../../lib/twClasses'

export default function ReporteGeneradoList() {
  const [data, setData] = useState([])
  const [loading, setLoading] = useState(true)

  const fetchData = () => {
    setLoading(true)
    api.get('/v1/reportes-generados').then((r) => setData(r.data)).catch(console.error).finally(() => setLoading(false))
  }

  useEffect(() => { fetchData() }, [])

  return (
    <div className="max-w-5xl mx-auto pb-12">
      <h1 className="text-3xl font-bold text-neutral-800 dark:text-neutral-100 mb-2">Reportes Generados</h1>
      <p className="text-neutral-500 font-medium mb-8">Historial de descargas de reportes PDF.</p>

      {loading && (
        <div className="flex flex-col items-center py-16 text-neutral-500">
          <div className="h-10 w-10 animate-spin rounded-full border-4 border-primary-100 border-t-primary mb-4"></div>
          <span className="font-semibold">Cargando...</span>
        </div>
      )}

      {!loading && data.length === 0 && (
        <div className="rounded-xl bg-white p-12 text-center shadow-4 dark:bg-surface-dark">
          <svg className="w-12 h-12 text-neutral-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
          <p className="text-lg font-semibold text-neutral-600 dark:text-neutral-300">No se han generado reportes aún</p>
        </div>
      )}

      {!loading && data.length > 0 && (
        <div className="overflow-hidden rounded-xl bg-white shadow-4 dark:bg-surface-dark">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className={tbl.head}>
                <tr>
                  <th className={tbl.th}>Tipo</th>
                  <th className={tbl.th}>Usuario</th>
                  <th className={tbl.th}>Fecha</th>
                  <th className={tbl.th}>Tiempo (s)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-neutral-100 dark:divide-neutral-700">
                {data.map((r) => (
                  <tr key={r.id_reporte} className={tbl.row}>
                    <td className={`${tbl.td} capitalize`}>{r.tipo_reporte}</td>
                    <td className={tbl.td}>{r.usuario?.username || '—'}</td>
                    <td className={tbl.td}>{new Date(r.fecha_generacion).toLocaleString('es-GT')}</td>
                    <td className={tbl.td}>{r.tiempo_generacion ?? '—'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  )
}
