import { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import api, { normList } from '../../api/axios'
import DataTable from '../../components/DataTable'

export default function InscripcionList() {
  const [data, setData] = useState([]); const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)
  const [q, setQ] = useState('')
  const [pagination, setPagination] = useState(null)
  const navigate = useNavigate()
  const fetchData = useCallback(async () => {
    setLoading(true)
    try { const r = await api.get('/v1/inscripciones', { params: { page, q: q || undefined } }); setData(r.data); setPagination({ current_page: r.data.current_page, last_page: r.data.last_page, total: r.data.total }) } catch(e) { console.error(e) } finally { setLoading(false) }
  }, [page, q])
  useEffect(() => { fetchData() }, [fetchData])
  const handleDelete = async (row) => { try { await api.delete(`/v1/inscripciones/${row.id_inscripcion}`); fetchData() } catch(e) { console.error(e); throw e } }
  const columns = [
    { key: 'alumno', label: 'Alumno', render: (row) => row.alumno ? `${row.alumno.nombre} ${row.alumno.apellido}` : '-', exportValue: (row) => row.alumno ? `${row.alumno.nombre} ${row.alumno.apellido}` : '-' },
    { key: 'asignacion', label: 'Asignación', render: (row) => row.asignacion?.curso?.nombre_curso || '-', exportValue: (row) => row.asignacion?.curso?.nombre_curso || '-' },
    { key: 'fecha_inscripcion', label: 'Fecha', render: (row) => new Date(row.fecha_inscripcion).toLocaleDateString('es-GT'), exportValue: (row) => row.fecha_inscripcion ? new Date(row.fecha_inscripcion).toLocaleDateString('es-GT') : '-' },
  ]
  const handleExport = async () => {
    const r = await api.get('/v1/inscripciones', { params: { per_page: 1000, q: q || undefined } })
    return normList(r.data)
  }
  return <DataTable title="Inscripciones" columns={columns} data={data} loading={loading}
    onAdd={() => navigate('/inscripciones/nuevo')}
    onDelete={handleDelete}
    pagination={pagination}
    onPageChange={setPage}
    onSearch={(s) => { setQ(s); setPage(1) }}
    onExport={handleExport} />
}
