import { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import api, { normList } from '../../api/axios'
import DataTable from '../../components/DataTable'

export default function EvaluacionList() {
  const [data, setData] = useState([]); const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)
  const [q, setQ] = useState('')
  const [pagination, setPagination] = useState(null)
  const navigate = useNavigate()
  const fetchData = useCallback(async () => {
    setLoading(true)
    try { const r = await api.get('/v1/evaluaciones', { params: { page, q: q || undefined } }); setData(r.data); setPagination({ current_page: r.data.current_page, last_page: r.data.last_page, total: r.data.total }) } catch(e) { console.error(e) } finally { setLoading(false) }
  }, [page, q])
  useEffect(() => { fetchData() }, [fetchData])
  const handleDelete = async (row) => { try { await api.delete(`/v1/evaluaciones/${row.id_evaluacion}`); fetchData() } catch(e) { console.error(e); throw e } }
  const columns = [
    { key: 'nombre', label: 'Nombre' },
    { key: 'asignacion', label: 'Asignación', render: (row) => row.asignacion?.curso?.nombre_curso || '-', exportValue: (row) => row.asignacion?.curso?.nombre_curso || '-' },
    { key: 'unidad_academica', label: 'Unidad' },
    { key: 'porcentaje', label: 'Porcentaje', render: (row) => row.porcentaje ? `${row.porcentaje}%` : '-', exportValue: (row) => row.porcentaje ? `${row.porcentaje}%` : '-' },
  ]
  const handleExport = async () => {
    const r = await api.get('/v1/evaluaciones', { params: { per_page: 1000, q: q || undefined } })
    return normList(r.data)
  }
  return <DataTable title="Evaluaciones" columns={columns} data={data} loading={loading}
    onAdd={() => navigate('/evaluaciones/nuevo')}
    onEdit={(row) => navigate(`/evaluaciones/${row.id_evaluacion}`)}
    onDelete={handleDelete}
    pagination={pagination}
    onPageChange={setPage}
    onSearch={(s) => { setQ(s); setPage(1) }}
    onExport={handleExport} />
}
