import { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import api, { normList } from '../../api/axios'
import DataTable from '../../components/DataTable'

export default function TareaList() {
  const [data, setData] = useState([]); const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)
  const [q, setQ] = useState('')
  const [pagination, setPagination] = useState(null)
  const navigate = useNavigate()
  const fetchData = useCallback(async () => {
    setLoading(true)
    try { const r = await api.get('/v1/tareas', { params: { page, q: q || undefined } }); setData(r.data); setPagination({ current_page: r.data.current_page, last_page: r.data.last_page, total: r.data.total }) } catch(e) { console.error(e) } finally { setLoading(false) }
  }, [page, q])
  useEffect(() => { fetchData() }, [fetchData])
  const handleDelete = async (row) => { try { await api.delete(`/v1/tareas/${row.id_tarea}`); fetchData() } catch(e) { console.error(e); throw e } }
  const columns = [
    { key: 'titulo', label: 'Título' },
    { key: 'asignacion', label: 'Asignación', render: (row) => row.asignacion?.curso?.nombre_curso || '-', exportValue: (row) => row.asignacion?.curso?.nombre_curso || '-' },
    { key: 'puntos', label: 'Puntos', render: (row) => row.puntos ?? '100 (por defecto)', exportValue: (row) => row.puntos ?? 100 },
    { key: 'fecha_entrega', label: 'Fecha de Entrega', render: (row) => row.fecha_entrega ? new Date(row.fecha_entrega).toLocaleDateString('es-GT') : '-', exportValue: (row) => row.fecha_entrega ? new Date(row.fecha_entrega).toLocaleDateString('es-GT') : '-' },
    { key: 'descripcion', label: 'Descripción' },
  ]
  const handleExport = async () => {
    const r = await api.get('/v1/tareas', { params: { per_page: 1000, q: q || undefined } })
    return normList(r.data)
  }
  return <DataTable title="Tareas" columns={columns} data={data} loading={loading}
    onAdd={() => navigate('/tareas/nuevo')}
    onEdit={(row) => navigate(`/tareas/${row.id_tarea}`)}
    onDelete={handleDelete}
    pagination={pagination}
    onPageChange={setPage}
    onSearch={(s) => { setQ(s); setPage(1) }}
    onExport={handleExport} />
}
