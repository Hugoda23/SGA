import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../../api/axios'
import DataTable from '../../components/DataTable'
import Modal from '../../components/Modal'
import { btn } from '../../lib/twClasses'

export default function PeriodoList() {
  const [data, setData] = useState([])
  const [loading, setLoading] = useState(true)
  const [cerrarRow, setCerrarRow] = useState(null)
  const [cerrando, setCerrando] = useState(false)
  const [resultado, setResultado] = useState(null)
  const navigate = useNavigate()

  const fetchData = async () => {
    try {
      const r = await api.get('/v1/periodos-academicos')
      setData(r.data)
    } catch (e) {
      console.error(e)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { fetchData() }, [])

  const handleDelete = async (row) => {
    try {
      await api.delete(`/v1/periodos-academicos/${row.id_periodo}`)
      fetchData()
    } catch (e) {
      console.error(e)
      throw e
    }
  }

  const handleCerrar = async () => {
    const row = cerrarRow
    setCerrando(true)
    try {
      const r = await api.post(`/v1/periodos-academicos/${row.id_periodo}/cerrar`)
      setResultado(r.data)
      setCerrarRow(null)
      fetchData()
    } catch (e) {
      setResultado({ message: e.response?.data?.message || 'No se pudo cerrar el periodo académico.' })
      setCerrarRow(null)
    } finally {
      setCerrando(false)
    }
  }

  const rowActions = [
    {
      label: 'Cerrar',
      show: (row) => row.estado !== 'cerrado',
      onClick: (row) => setCerrarRow(row),
      className: 'rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-bold text-primary transition-colors hover:bg-primary hover:text-white dark:bg-primary-100/10',
    },
  ]

  const columns = [
    { key: 'nombre', label: 'Periodo' },
    { key: 'fecha_inicio', label: 'Inicio', render: (row) => row.fecha_inicio ? new Date(row.fecha_inicio).toLocaleDateString('es-GT') : '-' },
    { key: 'fecha_fin', label: 'Fin', render: (row) => row.fecha_fin ? new Date(row.fecha_fin).toLocaleDateString('es-GT') : '-' },
    {
      key: 'estado',
      label: 'Estado',
      render: (row) => (
        <span className={`font-bold capitalize ${row.estado === 'cerrado' ? 'text-error' : row.estado === 'activo' ? 'text-success' : 'text-neutral-500'}`}>
          {row.estado || '-'}
        </span>
      ),
    },
  ]

  return (
    <>
      <DataTable
        title="Periodos Académicos"
        columns={columns}
        data={data}
        loading={loading}
        rowActions={rowActions}
        onAdd={() => navigate('/periodos/nuevo')}
        onEdit={(row) => navigate(`/periodos/${row.id_periodo}`)}
        onDelete={handleDelete}
      />

      <Modal
        open={!!cerrarRow}
        onClose={() => setCerrarRow(null)}
        title="Cerrar periodo académico"
        size="sm"
        footer={
          <>
            <button type="button" onClick={() => setCerrarRow(null)} className={btn.ghost}>
              Cancelar
            </button>
            <button type="button" onClick={handleCerrar} disabled={cerrando} className={`${btn.primary} disabled:opacity-60`}>
              {cerrando ? 'Cerrando...' : 'Sí, cerrar'}
            </button>
          </>
        }
      >
        <p className="text-sm text-neutral-600 dark:text-neutral-300">
          Al cerrar el periodo se recalcularán las notas finales, se ejecutará la promoción de los alumnos y
          quedarán bloqueadas las ediciones de calificaciones. Esta acción no se puede deshacer.
        </p>
      </Modal>

      <Modal
        open={!!resultado}
        onClose={() => setResultado(null)}
        title="Resultado"
        size="sm"
        footer={
          <button type="button" onClick={() => setResultado(null)} className={btn.primary}>
            Aceptar
          </button>
        }
      >
        {resultado && (
          <div className="space-y-2 text-sm">
            <p className="font-semibold text-neutral-700 dark:text-neutral-200">{resultado.message}</p>
            {resultado.resumen && (
              <ul className="list-disc pl-5 text-neutral-600 dark:text-neutral-300">
                <li>Alumnos evaluados: {resultado.resumen.total}</li>
                <li>Aprobados: {resultado.resumen.aprobados}</li>
                <li>Reprobados: {resultado.resumen.reprobados}</li>
                <li>Sin notas: {resultado.resumen.sin_notas}</li>
                <li>Egresados: {resultado.resumen.egresados}</li>
              </ul>
            )}
          </div>
        )}
      </Modal>
    </>
  )
}
