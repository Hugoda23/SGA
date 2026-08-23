import { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import api, { normList } from '../../api/axios'
import DataTable from '../../components/DataTable'

export default function AsignacionList() {
  const [data, setData] = useState([]); const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)
  const [q, setQ] = useState('')
  const [pagination, setPagination] = useState(null)
  const navigate = useNavigate()
  const fetchData = useCallback(async () => {
    setLoading(true)
    try { const r = await api.get('/v1/asignaciones', { params: { page, q: q || undefined } }); setData(r.data); setPagination({ current_page: r.data.current_page, last_page: r.data.last_page, total: r.data.total }) } catch(e) { console.error(e) } finally { setLoading(false) }
  }, [page, q])
  useEffect(() => { fetchData() }, [fetchData])
  const handleDelete = async (row) => { try { await api.delete(`/v1/asignaciones/${row.id_asignacion}`); fetchData() } catch(e) { console.error(e); throw e } }
  const columns = [
    { key: 'curso', label: 'Curso', render: (row) => row.curso?.nombre_curso || '-', exportValue: (row) => row.curso?.nombre_curso || '-' },
    { key: 'catedratico', label: 'Catedrático', render: (row) => row.catedratico ? `${row.catedratico.nombre} ${row.catedratico.apellido}` : '-', exportValue: (row) => row.catedratico ? `${row.catedratico.nombre} ${row.catedratico.apellido}` : '-' },
    { key: 'aula', label: 'Aula', render: (row) => row.aula?.nombre_aula || '-', exportValue: (row) => row.aula?.nombre_aula || '-' },
    { key: 'periodo', label: 'Periodo', render: (row) => row.periodo?.nombre || '-', exportValue: (row) => row.periodo?.nombre || '-' },
    { key: 'seccion', label: 'Sección' },
    { key: 'grado', label: 'Grado' },
  ]
  const handleExport = async () => {
    const r = await api.get('/v1/asignaciones', { params: { per_page: 1000, q: q || undefined } })
    return normList(r.data)
  }
  return <DataTable title="Asignaciones" columns={columns} data={data} loading={loading}
    onAdd={() => navigate('/asignaciones/nuevo')}
    onEdit={(row) => navigate(`/asignaciones/${row.id_asignacion}`)}
    onDelete={handleDelete}
    pagination={pagination}
    onPageChange={setPage}
    onSearch={(s) => { setQ(s); setPage(1) }}
    onExport={handleExport} />
}
